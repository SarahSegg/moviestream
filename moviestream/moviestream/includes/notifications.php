<?php
// Flash message system
function flash($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function show_notifications() {
    $flash = get_flash();
    if ($flash) {
        $type_class = '';
        $icon = '';
        
        switch ($flash['type']) {
            case 'success':
                $type_class = 'notification-success';
                $icon = 'fa-check-circle';
                break;
            case 'error':
                $type_class = 'notification-error';
                $icon = 'fa-exclamation-circle';
                break;
            case 'warning':
                $type_class = 'notification-warning';
                $icon = 'fa-exclamation-triangle';
                break;
            default:
                $type_class = 'notification-info';
                $icon = 'fa-info-circle';
        }
        
        echo "<div class='notification $type_class'>
                <i class='fas $icon'></i>
                <p>{$flash['message']}</p>
              </div>";
    }
}
?>