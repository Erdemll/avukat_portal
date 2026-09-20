const userId = Number(document.body.dataset.authUserId || 0);
const notificationCenter = document.querySelector('[data-notification-center]');

if (userId && notificationCenter && window.Echo) {
    const countBadge = notificationCenter.querySelector('[data-notification-count]');
    const notificationList = notificationCenter.querySelector('[data-notification-list]');
    const toastContainer = document.querySelector('[data-notification-toasts]');

    const setUnreadCount = count => {
        const normalizedCount = Math.max(0, count);
        countBadge.textContent = String(normalizedCount);
        countBadge.setAttribute('aria-label', `${normalizedCount} okunmamış bildirim`);
        countBadge.classList.toggle('hidden', normalizedCount === 0);
        countBadge.classList.toggle('inline-flex', normalizedCount > 0);
    };

    const notificationLink = conversationId => {
        const url = new URL(document.body.dataset.messagesUrl, window.location.origin);
        url.searchParams.set('conversation', conversationId);

        return url.toString();
    };

    const addNotificationItem = message => {
        notificationList.querySelector('[data-notification-empty]')?.remove();

        const item = document.createElement('li');
        item.className = 'bg-indigo-50/60';
        item.dataset.notificationItem = '';
        item.dataset.notificationConversation = message.conversation_id;

        const link = document.createElement('a');
        link.className = 'block px-4 py-3 hover:bg-slate-50';
        link.href = notificationLink(message.conversation_id);

        const label = document.createElement('span');
        label.className = 'block text-sm font-semibold text-slate-900';
        label.textContent = `Av. ${message.sender_name} size yeni bir mesaj gönderdi.`;

        const time = document.createElement('span');
        time.className = 'mt-1 block text-xs text-slate-400';
        time.textContent = 'Şimdi';

        link.append(label, time);
        item.append(link);
        notificationList.prepend(item);

        while (notificationList.querySelectorAll('[data-notification-item]').length > 5) {
            notificationList.querySelector('[data-notification-item]:last-child')?.remove();
        }
    };

    const showToast = message => {
        const toast = document.createElement('a');
        toast.className = 'pointer-events-auto flex w-full items-start gap-3 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-slate-800 shadow-xl';
        toast.href = notificationLink(message.conversation_id);

        const icon = document.createElement('span');
        icon.className = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800';
        icon.textContent = message.sender_name.trim().charAt(0).toLocaleUpperCase('tr-TR');

        const content = document.createElement('span');
        content.className = 'min-w-0';

        const title = document.createElement('strong');
        title.className = 'block text-sm text-slate-950';
        title.textContent = 'Yeni mesaj';

        const description = document.createElement('span');
        description.className = 'mt-0.5 block truncate text-sm text-slate-600';
        description.textContent = `Av. ${message.sender_name} size bir mesaj gönderdi.`;

        content.append(title, description);
        toast.append(icon, content);
        toastContainer.append(toast);
        window.setTimeout(() => toast.remove(), 6000);
    };

    window.addEventListener('messages:read', event => {
        const conversationId = String(event.detail.conversationId);
        const notificationsRead = Number(event.detail.notificationsRead || 0);

        if (notificationsRead > 0) {
            setUnreadCount(Number(countBadge.textContent || 0) - notificationsRead);
        }

        notificationList.querySelectorAll('[data-notification-item]').forEach(item => {
            if (item.dataset.notificationConversation === conversationId) {
                item.classList.remove('bg-indigo-50/60');
                item.querySelector('span')?.classList.remove('font-semibold', 'text-slate-900');
                item.querySelector('span')?.classList.add('text-slate-600');
            }
        });
    });

    window.Echo.private(`user.${userId}`).listen('.message.sent', event => {
        const message = event.message;
        const activeConversationId = document.querySelector('[data-messages-root]')?.dataset.activeConversationId;

        if (!message || Number(message.sender_id) === userId) {
            return;
        }

        setUnreadCount(Number(countBadge.textContent || 0) + 1);
        addNotificationItem(message);

        if (Number(activeConversationId) !== Number(message.conversation_id)) {
            showToast(message);
        }
    });
}
