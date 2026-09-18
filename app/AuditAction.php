<?php

namespace App;

enum AuditAction: string
{
    case UserLogin = 'USER_LOGIN';
    case UserLoginFailed = 'USER_LOGIN_FAILED';
    case UserLogout = 'USER_LOGOUT';
    case EventCreated = 'EVENT_CREATED';
    case EventViewed = 'EVENT_VIEWED';
    case EventUpdated = 'EVENT_UPDATED';
    case EventStatusChanged = 'EVENT_STATUS_CHANGED';
    case EventClosed = 'EVENT_CLOSED';
    case EventPriorityChanged = 'EVENT_PRIORITY_CHANGED';
    case EventUpdateCreated = 'EVENT_UPDATE_CREATED';
    case DocumentUploaded = 'DOCUMENT_UPLOADED';
    case DocumentDownloaded = 'DOCUMENT_DOWNLOADED';
    case DocumentDeleted = 'DOCUMENT_DELETED';
    case UserCreated = 'USER_CREATED';
    case UserUpdated = 'USER_UPDATED';
    case UserActivated = 'USER_ACTIVATED';
    case UserDeactivated = 'USER_DEACTIVATED';
    case UserRoleChanged = 'USER_ROLE_CHANGED';
    case EventTypeCreated = 'EVENT_TYPE_CREATED';
    case EventTypeUpdated = 'EVENT_TYPE_UPDATED';
    case EventTypeActivated = 'EVENT_TYPE_ACTIVATED';
    case EventTypeDeactivated = 'EVENT_TYPE_DEACTIVATED';
    case PasswordResetRequested = 'PASSWORD_RESET_REQUESTED';
    case PasswordResetCompleted = 'PASSWORD_RESET_COMPLETED';
}
