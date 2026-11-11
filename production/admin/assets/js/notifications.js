// Base para notificações administrativas.
window.SiteToolsNotifications = {
    show(message, type = 'info') {
        const notice = document.createElement('div');
        notice.className = `admin-notice admin-notice-${type}`;
        notice.textContent = message;
        notice.setAttribute('role', 'status');
        Object.assign(notice.style, {
            position: 'fixed', right: '20px', bottom: '20px', zIndex: '1000',
            background: 'var(--surface)', color: 'var(--text)',
            border: '1px solid var(--border)', borderRadius: '9px',
            padding: '12px 16px', boxShadow: 'var(--shadow)'
        });
        document.body.appendChild(notice);
        window.setTimeout(() => notice.remove(), 3500);
    }
};
