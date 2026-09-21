const root = document.querySelector('[data-messages-root]');

if (root) {
    const config = JSON.parse(document.querySelector('[data-messages-config]').textContent);
    const currentUserId = Number(root.dataset.currentUserId);
    const csrfToken = root.dataset.csrfToken;
    const sidebar = root.querySelector('[data-messages-sidebar]');
    const chatPanel = root.querySelector('[data-chat-panel]');
    const placeholder = root.querySelector('[data-chat-placeholder]');
    const content = root.querySelector('[data-chat-content]');
    const loading = root.querySelector('[data-chat-loading]');
    const messageScroll = root.querySelector('[data-message-scroll]');
    const messageList = root.querySelector('[data-message-list]');
    const emptyState = root.querySelector('[data-message-empty]');
    const olderWrapper = root.querySelector('[data-older-wrapper]');
    const olderButton = root.querySelector('[data-load-older]');
    const form = root.querySelector('[data-message-form]');
    const input = root.querySelector('[data-message-input]');
    const submit = root.querySelector('[data-message-submit]');
    const error = root.querySelector('[data-message-error]');
    const seenMessageIds = new Set();
    const lawyerState = new Map(config.lawyers.map(lawyer => [Number(lawyer.id), lawyer]));
    const mobileLayout = window.matchMedia('(max-width: 1023px)');
    let activeLawyerId = null;
    let activeConversation = null;
    let activeChannelName = null;
    let nextBefore = null;
    let openingSequence = 0;
    let mobileChatVisible = false;

    const initials = name => name.trim().split(/\s+/u).slice(0, 2).map(part => part[0]?.toLocaleUpperCase('tr-TR') ?? '').join('');

    const formatTime = value => {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        const today = new Date();
        const sameDay = date.getFullYear() === today.getFullYear()
            && date.getMonth() === today.getMonth()
            && date.getDate() === today.getDate();

        return new Intl.DateTimeFormat('tr-TR', sameDay
            ? { hour: '2-digit', minute: '2-digit' }
            : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }
        ).format(date);
    };

    const request = async (url, options = {}) => {
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...options.headers,
        };
        const socketId = window.Echo?.socketId();

        if (socketId) {
            headers['X-Socket-ID'] = socketId;
        }

        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(validationMessage || payload.message || 'İşlem tamamlanamadı.');
        }

        return payload;
    };

    const lawyerRow = lawyerId => root.querySelector(`[data-lawyer-row][data-lawyer-id="${lawyerId}"]`);

    const updateSidebar = (message, incrementUnread = false) => {
        const lawyerId = message.sender_id === currentUserId ? activeLawyerId : Number(message.sender_id);
        const row = lawyerRow(lawyerId);

        if (!row) {
            return;
        }

        const lawyer = lawyerState.get(lawyerId);
        lawyer.conversation_id = Number(message.conversation_id);
        row.dataset.conversationId = message.conversation_id;
        row.querySelector('[data-lawyer-preview]').textContent = message.body.length > 48 ? `${message.body.slice(0, 48)}…` : message.body;
        const time = row.querySelector('[data-lawyer-time]');
        time.dateTime = message.created_at;
        time.textContent = formatTime(message.created_at);

        if (incrementUnread) {
            const badge = row.querySelector('[data-unread-count]');
            const count = Number(badge.textContent || 0) + 1;
            badge.textContent = String(count);
            badge.setAttribute('aria-label', `${count} okunmamış mesaj`);
            badge.classList.remove('hidden');
            badge.classList.add('inline-flex');
        }
    };

    const clearUnread = lawyerId => {
        const badge = lawyerRow(lawyerId)?.querySelector('[data-unread-count]');

        if (badge) {
            badge.textContent = '0';
            badge.setAttribute('aria-label', '0 okunmamış mesaj');
            badge.classList.add('hidden');
            badge.classList.remove('inline-flex');
        }
    };

    const messageElement = message => {
        const own = Number(message.sender_id) === currentUserId;
        const wrapper = document.createElement('article');
        wrapper.className = `flex ${own ? 'justify-end' : 'justify-start'}`;
        wrapper.dataset.messageId = message.id;

        const bubble = document.createElement('div');
        bubble.className = own
            ? 'max-w-[85%] rounded-2xl rounded-br-md bg-indigo-900 px-4 py-3 text-white shadow-sm sm:max-w-[70%]'
            : 'max-w-[85%] rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-slate-800 shadow-sm sm:max-w-[70%]';

        const body = document.createElement('p');
        body.className = 'whitespace-pre-wrap break-words text-sm leading-6';
        body.textContent = message.body;

        const time = document.createElement('time');
        time.className = `mt-1.5 block text-right text-[0.68rem] ${own ? 'text-indigo-200' : 'text-slate-400'}`;
        time.dateTime = message.created_at;
        time.textContent = formatTime(message.created_at);

        bubble.append(body, time);
        wrapper.append(bubble);

        return wrapper;
    };

    const scrollToBottom = () => {
        messageScroll.scrollTop = messageScroll.scrollHeight;
    };

    const appendMessage = (message, forceScroll = false) => {
        if (seenMessageIds.has(Number(message.id))) {
            return;
        }

        const nearBottom = messageScroll.scrollHeight - messageScroll.scrollTop - messageScroll.clientHeight < 140;
        seenMessageIds.add(Number(message.id));
        messageList.append(messageElement(message));
        emptyState.classList.add('hidden');

        if (forceScroll || nearBottom) {
            requestAnimationFrame(scrollToBottom);
        }
    };

    const markRead = async () => {
        if (!activeConversation) {
            return;
        }

        clearUnread(activeLawyerId);

        try {
            const payload = await request(activeConversation.read_url, { method: 'POST', body: '{}' });
            window.dispatchEvent(new CustomEvent('messages:read', {
                detail: {
                    conversationId: activeConversation.id,
                    notificationsRead: payload.notifications_read,
                },
            }));
        } catch {
            // Refreshing the conversation will recalculate unread state from the database.
        }
    };

    const receiveMessage = event => {
        const message = event.message;

        if (!message || seenMessageIds.has(Number(message.id))) {
            return;
        }

        const isActive = Number(message.conversation_id) === Number(activeConversation?.id);
        updateSidebar(message, !isActive && Number(message.sender_id) !== currentUserId);

        if (isActive) {
            appendMessage(message);

            if (Number(message.sender_id) !== currentUserId) {
                markRead();
            }
        }
    };

    const listenToActiveConversation = conversationId => {
        if (!window.Echo) {
            return;
        }

        if (activeChannelName) {
            window.Echo.leave(activeChannelName);
        }

        activeChannelName = `conversation.${conversationId}`;
        window.Echo.private(activeChannelName).listen('.message.sent', receiveMessage);
    };

    const applyResponsiveState = () => {
        if (!mobileLayout.matches) {
            sidebar.classList.remove('hidden');
            chatPanel.classList.remove('hidden', 'flex');

            return;
        }

        sidebar.classList.toggle('hidden', mobileChatVisible);
        chatPanel.classList.toggle('hidden', !mobileChatVisible);
        chatPanel.classList.toggle('flex', mobileChatVisible);
    };

    const setMobileChatVisible = visible => {
        mobileChatVisible = visible;
        applyResponsiveState();
    };

    const renderConversation = payload => {
        activeConversation = payload.conversation;
        root.dataset.activeConversationId = payload.conversation.id;
        nextBefore = payload.pagination.next_before;
        root.querySelector('[data-chat-name]').textContent = `Av. ${payload.conversation.participant.name}`;
        root.querySelector('[data-chat-initials]').textContent = initials(payload.conversation.participant.name);
        root.querySelector('[data-chat-status]').textContent = payload.conversation.participant.is_active ? 'Özel görüşme' : 'Pasif kullanıcı · geçmiş erişilebilir';
        messageList.replaceChildren();
        seenMessageIds.clear();
        payload.messages.forEach(message => appendMessage(message));
        emptyState.classList.toggle('hidden', payload.messages.length > 0);
        olderWrapper.classList.toggle('hidden', !payload.pagination.has_more);
        olderWrapper.classList.toggle('flex', payload.pagination.has_more);
        loading.classList.add('hidden');
        listenToActiveConversation(payload.conversation.id);
        markRead();
        requestAnimationFrame(scrollToBottom);
        input.focus();
    };

    const openConversation = async row => {
        const sequence = ++openingSequence;
        activeLawyerId = Number(row.dataset.lawyerId);
        error.textContent = '';
        placeholder.classList.add('hidden');
        content.classList.remove('hidden');
        content.classList.add('flex');
        loading.classList.remove('hidden');
        setMobileChatVisible(true);
        root.querySelectorAll('[data-lawyer-row]').forEach(item => item.classList.toggle('bg-indigo-50', item === row));

        try {
            let conversationId = Number(row.dataset.conversationId || 0);
            let endpoints;

            if (!conversationId) {
                endpoints = await request(root.dataset.conversationStoreUrl, {
                    method: 'POST',
                    body: JSON.stringify({ user_id: activeLawyerId }),
                });
                conversationId = Number(endpoints.id);
                row.dataset.conversationId = String(conversationId);
                lawyerState.get(activeLawyerId).conversation_id = conversationId;
            }

            const showUrl = endpoints?.show_url || `/messages/conversations/${conversationId}`;
            const payload = await request(showUrl);

            if (sequence === openingSequence) {
                renderConversation(payload);
            }
        } catch (caught) {
            if (sequence === openingSequence) {
                loading.classList.add('hidden');
                error.textContent = caught.message;
            }
        }
    };

    root.querySelectorAll('[data-lawyer-time]').forEach(element => {
        element.textContent = formatTime(element.dateTime);
    });

    root.querySelectorAll('[data-lawyer-row]').forEach(row => row.addEventListener('click', event => {
        event.preventDefault();
        openConversation(row);
    }));

    root.querySelector('[data-lawyer-search]').addEventListener('input', event => {
        const needle = event.target.value.trim().toLocaleLowerCase('tr-TR');
        let visible = 0;

        root.querySelectorAll('[data-lawyer-row]').forEach(row => {
            const matches = `${row.dataset.lawyerName} ${row.dataset.lawyerEmail}`.toLocaleLowerCase('tr-TR').includes(needle);
            row.classList.toggle('hidden', !matches);
            visible += matches ? 1 : 0;
        });

        root.querySelector('[data-search-empty]').classList.toggle('hidden', visible > 0);
    });

    root.querySelector('[data-chat-back]').addEventListener('click', () => setMobileChatVisible(false));
    mobileLayout.addEventListener('change', applyResponsiveState);
    applyResponsiveState();

    olderButton.addEventListener('click', async () => {
        if (!nextBefore || !activeConversation) {
            return;
        }

        olderButton.disabled = true;
        olderButton.textContent = 'Yükleniyor…';
        const previousHeight = messageScroll.scrollHeight;

        try {
            const payload = await request(`${activeConversation.show_url}?before=${nextBefore}`);
            const fragment = document.createDocumentFragment();

            payload.messages.forEach(message => {
                if (!seenMessageIds.has(Number(message.id))) {
                    seenMessageIds.add(Number(message.id));
                    fragment.append(messageElement(message));
                }
            });
            messageList.prepend(fragment);
            messageScroll.scrollTop += messageScroll.scrollHeight - previousHeight;
            nextBefore = payload.pagination.next_before;
            olderWrapper.classList.toggle('hidden', !payload.pagination.has_more);
            olderWrapper.classList.toggle('flex', payload.pagination.has_more);
        } catch (caught) {
            error.textContent = caught.message;
        } finally {
            olderButton.disabled = false;
            olderButton.textContent = 'Daha eski mesajları yükle';
        }
    });

    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const body = input.value.trim();

        if (!body || !activeConversation || submit.disabled) {
            return;
        }

        submit.disabled = true;
        root.querySelector('[data-submit-label]').textContent = 'Gönderiliyor…';
        error.textContent = '';

        try {
            const payload = await request(activeConversation.message_store_url, {
                method: 'POST',
                body: JSON.stringify({ body }),
            });
            appendMessage(payload.message, true);
            updateSidebar(payload.message);
            input.value = '';
            input.focus();
        } catch (caught) {
            error.textContent = caught.message;
        } finally {
            submit.disabled = false;
            root.querySelector('[data-submit-label]').textContent = 'Gönder';
        }
    });

    window.Echo?.private(`user.${currentUserId}`).listen('.message.sent', receiveMessage);

    if (config.initial_conversation_id) {
        const initialRow = root.querySelector(`[data-lawyer-row][data-conversation-id="${config.initial_conversation_id}"]`);
        initialRow?.click();
    } else if (config.initial_lawyer_id) {
        const initialRow = root.querySelector(`[data-lawyer-row][data-lawyer-id="${config.initial_lawyer_id}"]`);
        initialRow?.click();
    }
}
