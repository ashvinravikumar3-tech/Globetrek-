<?php
$allowed_roles = ['customer'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        $error = "Subject and message are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO queries (customer_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['name'], $_SESSION['email'] ?? '', $subject, $message]);
        $success = "Your query has been submitted.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM queries WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$myQueries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section-header" style="margin-top: 40px; margin-bottom: 30px;">
    <div>
        <h2>Support Center</h2>
        <p style="color: var(--text-secondary); margin-top: 5px;">Submit your queries and keep track of support ticket status.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="contact-message success" style="margin-bottom: 25px;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="contact-message error" style="margin-bottom: 25px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="queries-container" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start; margin-bottom: 60px;">
    <!-- Left Column: Submit Query Form -->
    <div class="card" style="padding: 30px; position: sticky; top: 130px;">
        <h3 style="font-size: 1.5rem; margin-bottom: 10px; font-family: var(--font-heading);">Submit a New Query</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 25px;">Our support agents typically reply within 24 hours.</p>
        
        <!-- Client-side validation message placeholder -->
        <div id="jsErrorAlert" class="contact-message error" style="display: none; margin-bottom: 20px; font-size: 0.9rem; padding: 10px 12px; border-radius: 8px;"></div>

        <form id="queryForm" method="POST" action="cust_queries.php" style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-weight: 500; font-size: 0.95rem; color: var(--text-primary);">Subject</label>
                <input id="subjectInput" type="text" name="subject" placeholder="e.g. Booking confirmation issue" required style="width: 100%; max-width: none; margin-bottom: 0; padding: 12px 16px; border-radius: 10px;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-weight: 500; font-size: 0.95rem; color: var(--text-primary);">Message Details</label>
                <textarea id="messageInput" name="message" rows="5" placeholder="Explain your query in detail..." required style="width: 100%; max-width: none; margin-bottom: 0; padding: 12px 16px; border-radius: 10px; resize: vertical; min-height: 120px;"></textarea>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                    <span id="validationWarning" style="font-size: 0.8rem; color: #f87171; display: none;">Min length: 10 chars</span>
                    <span id="charCount" style="font-size: 0.8rem; color: var(--text-secondary); margin-left: auto;">0 characters</span>
                </div>
            </div>

            <button type="submit" class="btn" style="align-self: flex-start; margin-top: 10px;">
                Submit Ticket
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </form>
    </div>

    <!-- Right Column: My Queries List -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <h3 style="font-size: 1.5rem; font-family: var(--font-heading); margin-bottom: 5px;">My Tickets</h3>
        
        <?php if (empty($myQueries)): ?>
            <div class="card" style="padding: 40px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <div>
                    <h4 style="font-size: 1.1rem; margin-bottom: 5px;">No Queries Yet</h4>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">You haven't submitted any queries yet. Use the form on the left to submit a new support ticket.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($myQueries as $q): ?>
                <div class="card" style="padding: 25px; transition: var(--transition-smooth);">
                    <!-- Ticket Header -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                        <div>
                            <h4 style="font-size: 1.25rem; font-family: var(--font-heading); font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                <?= htmlspecialchars($q['subject']) ?>
                            </h4>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">
                                Submitted on <?= date('M d, Y \a\t h:i A', strtotime($q['created_at'])) ?>
                            </span>
                        </div>
                        <div>
                            <?php if ($q['status'] === 'answered'): ?>
                                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e;"></span>
                                    Answered
                                </span>
                            <?php else: ?>
                                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2);">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #3b82f6; animation: pulse 2s infinite;"></span>
                                    Open
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Ticket Body -->
                    <div style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 15px; word-break: break-word;">
                        <?= nl2br(htmlspecialchars($q['message'])) ?>
                    </div>

                    <!-- Staff Reply -->
                    <?php if ($q['status'] === 'answered' && !empty($q['staff_reply'])): ?>
                        <div style="background: rgba(255, 255, 255, 0.02); border-left: 3px solid var(--accent-solid); padding: 18px; border-radius: 0 12px 12px 0; margin-top: 20px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                </div>
                                <span style="font-size: 0.85rem; font-weight: 700; color: white;">GlobeTrek Support Agent</span>
                            </div>
                            <div style="font-size: 0.95rem; color: var(--text-primary); line-height: 1.6; word-break: break-word;">
                                <?= nl2br(htmlspecialchars($q['staff_reply'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Responsive grid and animations style tag -->
<style>
@keyframes pulse {
    0% { transform: scale(0.9); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.5; }
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}
@media (max-width: 900px) {
    .queries-container {
        grid-template-columns: 1fr !important;
    }
    .card {
        position: static !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('queryForm');
    const subject = document.getElementById('subjectInput');
    const message = document.getElementById('messageInput');
    const alertBox = document.getElementById('jsErrorAlert');
    const charCount = document.getElementById('charCount');
    const validationWarning = document.getElementById('validationWarning');

    // Real-time character counter & warning visual style
    message.addEventListener('input', () => {
        const length = message.value.trim().length;
        charCount.textContent = `${length} character${length === 1 ? '' : 's'}`;

        if (length > 0 && length < 10) {
            validationWarning.style.display = 'inline';
            message.style.borderColor = '#f87171';
            message.style.boxShadow = '0 0 0 3px rgba(248, 113, 113, 0.15)';
        } else {
            validationWarning.style.display = 'none';
            message.style.borderColor = '';
            message.style.boxShadow = '';
        }
    });

    // Subject input feedback
    subject.addEventListener('input', () => {
        const length = subject.value.trim().length;
        if (length > 0 && length < 5) {
            subject.style.borderColor = '#f87171';
            subject.style.boxShadow = '0 0 0 3px rgba(248, 113, 113, 0.15)';
        } else {
            subject.style.borderColor = '';
            subject.style.boxShadow = '';
        }
    });

    // Form Submission Interceptor
    form.addEventListener('submit', (e) => {
        let errors = [];
        const subjectVal = subject.value.trim();
        const messageVal = message.value.trim();

        // Reset inputs
        subject.style.borderColor = '';
        subject.style.boxShadow = '';
        message.style.borderColor = '';
        message.style.boxShadow = '';

        if (subjectVal.length < 5) {
            errors.push('Subject must be at least 5 characters long.');
            subject.style.borderColor = '#f87171';
            subject.style.boxShadow = '0 0 0 3px rgba(248, 113, 113, 0.15)';
        }
        if (messageVal.length < 10) {
            errors.push('Message details must be at least 10 characters long.');
            message.style.borderColor = '#f87171';
            message.style.boxShadow = '0 0 0 3px rgba(248, 113, 113, 0.15)';
        }

        if (errors.length > 0) {
            e.preventDefault();
            
            // Render error alert
            alertBox.innerHTML = errors.join('<br>');
            alertBox.style.display = 'block';
            
            // Smooth scroll to alert box if needed
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Shake effect on alert
            alertBox.style.animation = 'none';
            void alertBox.offsetWidth; // Trigger reflow to restart animation
            alertBox.style.animation = 'shake 0.4s ease-in-out';
        } else {
            alertBox.style.display = 'none';
        }
    });
});
</script>

<?php require 'footer.php'; ?>