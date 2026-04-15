<?php
/**
 * Support Ticket Controller (Tenant side)
 * Allows tenant users to create and view support tickets.
 */
class TicketController extends Controller {

    private PDO $masterDb;

    public function __construct() {
        parent::__construct();
        $this->masterDb = Tenant::getMasterDB();
    }

    public function index(): void {
        $this->requireAuth();

        $tenantId = Tenant::current()['id'];
        $status = $_GET['status'] ?? '';

        $sql = "SELECT * FROM tickets WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($status && in_array($status, ['open', 'in_progress', 'waiting', 'resolved', 'closed'])) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY FIELD(status, 'open', 'in_progress', 'waiting', 'resolved', 'closed'), updated_at DESC";

        $stmt = $this->masterDb->prepare($sql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();

        // Count by status
        $countStmt = $this->masterDb->prepare("SELECT status, COUNT(*) as cnt FROM tickets WHERE tenant_id = ? GROUP BY status");
        $countStmt->execute([$tenantId]);
        $counts = ['all' => 0, 'open' => 0, 'in_progress' => 0, 'waiting' => 0, 'resolved' => 0, 'closed' => 0];
        foreach ($countStmt->fetchAll() as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
            $counts['all'] += (int)$row['cnt'];
        }

        $pageTitle = __('tickets.title') ?: 'Support';
        $this->render('tickets/index', compact('tickets', 'counts', 'status', 'pageTitle'));
    }

    public function create(): void {
        $this->requireAuth();
        $pageTitle = __('tickets.create') ?: 'Nouveau ticket';
        $this->render('tickets/create', compact('pageTitle'));
    }

    public function store(): void {
        $this->requireAuth();

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $priority = $_POST['priority'] ?? 'medium';
        $user = currentUser();

        if (empty($subject) || empty($message)) {
            $this->flash('error', 'Le sujet et le message sont requis.');
            $this->redirect('/tickets/create');
            return;
        }

        $ticketId = $this->generateUUID();
        $tenant = Tenant::current();

        $stmt = $this->masterDb->prepare(
            "INSERT INTO tickets (id, tenant_id, user_name, user_email, subject, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$ticketId, $tenant['id'], $user['full_name'], $user['email'] ?? '', $subject, $category, $priority]);

        // First message
        $msgStmt = $this->masterDb->prepare(
            "INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message) VALUES (?, 'client', ?, ?)"
        );
        $msgStmt->execute([$ticketId, $user['full_name'], $message]);

        $this->flash('success', 'Ticket cree avec succes. Nous vous repondrons rapidement.');
        $this->redirect('/tickets/view/' . $ticketId);
    }

    public function view(string $id): void {
        $this->requireAuth();

        $tenantId = Tenant::current()['id'];
        $stmt = $this->masterDb->prepare("SELECT * FROM tickets WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            $this->flash('error', 'Ticket introuvable.');
            $this->redirect('/tickets');
            return;
        }

        $msgStmt = $this->masterDb->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
        $msgStmt->execute([$id]);
        $messages = $msgStmt->fetchAll();

        $pageTitle = 'Ticket #' . substr($id, 0, 8);
        $this->render('tickets/view', compact('ticket', 'messages', 'pageTitle'));
    }

    public function reply(string $id): void {
        $this->requireAuth();

        $tenantId = Tenant::current()['id'];
        $stmt = $this->masterDb->prepare("SELECT * FROM tickets WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        $ticket = $stmt->fetch();

        if (!$ticket || in_array($ticket['status'], ['closed', 'resolved'])) {
            $this->flash('error', 'Impossible de repondre a ce ticket.');
            $this->redirect('/tickets');
            return;
        }

        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            $this->flash('error', 'Le message est requis.');
            $this->redirect('/tickets/view/' . $id);
            return;
        }

        $user = currentUser();
        $msgStmt = $this->masterDb->prepare(
            "INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message) VALUES (?, 'client', ?, ?)"
        );
        $msgStmt->execute([$id, $user['full_name'], $message]);

        // Reopen if was waiting
        if ($ticket['status'] === 'waiting') {
            $this->masterDb->prepare("UPDATE tickets SET status = 'open' WHERE id = ?")->execute([$id]);
        }

        $this->flash('success', 'Reponse envoyee.');
        $this->redirect('/tickets/view/' . $id);
    }
}
