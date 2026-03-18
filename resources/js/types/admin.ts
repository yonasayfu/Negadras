export type ManagedRole = {
    id: number;
    name: string;
    description: string | null;
    permissions: string[];
    usersCount: number;
    permissionsCount: number;
    isSystem: boolean;
    canDelete: boolean;
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    isCurrentUser: boolean;
    emailVerifiedAt: string | null;
    createdAt: string | null;
    notes?: ManagedNote[];
};

export type ManagedPage = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content?: string;
    seoTitle?: string | null;
    seoDescription?: string | null;
    status: string;
    statusLabel: string;
    statusTone: string;
    isPublished: boolean;
    publishedAt: string | null;
    updatedAt: string | null;
    publicUrl: string | null;
    isDeleted: boolean;
    deletedAt: string | null;
    notes?: ManagedNote[];
};

export type ManagedSeason = {
    id: number;
    name: string;
    year: number;
    slug: string;
    status: string;
    statusLabel: string;
    statusTone: string;
    registrationOpenAt: string | null;
    registrationCloseAt: string | null;
    description: string | null;
    stagesCount: number;
    createdAt: string | null;
};

export type ManagedStage = {
    id: number;
    seasonId: string;
    seasonName: string | null;
    name: string;
    code: string;
    type: string;
    typeLabel: string;
    orderIndex: number;
    startsAt: string | null;
    endsAt: string | null;
    status: string;
    statusLabel: string;
    statusTone: string;
    isLiveStage: boolean;
};

export type ManagedIndustry = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    isActive: boolean;
    createdAt: string | null;
};

export type SelectOption = {
    value: string;
    label: string;
};

export type ManagedImportRun = {
    id: number;
    fileName: string;
    status: string;
    rowsCount: number;
    validRowsCount: number;
    importedRowsCount: number;
    completedAt: string | null;
    createdAt: string | null;
};

export type PageImportPreviewRow = {
    line: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    seo_title: string | null;
    seo_description: string | null;
    status: string;
    valid: boolean;
    errors: string[];
};

export type PageImportPreview = {
    importRunId: number;
    fileName: string;
    rows: PageImportPreviewRow[];
    summary: {
        rows: number;
        validRows: number;
        invalidRows: number;
    };
};

export type ManagedSettingField = {
    key: string;
    label: string;
    description: string;
    type: string;
    placeholder: string | null;
    rows?: number;
    value: string | null;
};

export type ManagedSettingGroup = {
    key: string;
    title: string;
    description: string;
    fields: ManagedSettingField[];
};

export type ManagedMedia = {
    id: number;
    collection: string;
    originalName: string;
    extension: string | null;
    mimeType: string | null;
    size: number;
    uploadedBy: string | null;
    createdAt: string | null;
    downloadUrl: string;
};

export type ManagedNote = {
    id: number;
    content: string;
    author: string | null;
    createdAt: string | null;
    canDelete: boolean;
};

export type NoteTarget = {
    type: string;
    id: number;
    title: string;
};

export type RoleOption = {
    name: string;
    label: string;
    description: string | null;
    usersCount: number;
};

export type PermissionDefinition = {
    name: string;
    label: string;
    description: string;
};

export type PermissionGroup = {
    key: string;
    title: string;
    permissions: PermissionDefinition[];
};

export type ResourceFilters = {
    search: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedResource<T> = {
    data: T[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type FlashMessages = {
    success?: string | null;
    error?: string | null;
};

export type ManagedNotification = {
    id: string;
    title: string;
    message: string;
    actionUrl: string | null;
    actionLabel: string | null;
    level: string;
    readAt: string | null;
    createdAt: string | null;
};

export type NotificationStats = {
    unreadCount: number;
    totalCount: number;
};

export type NotificationFilters = {
    read: string;
};

export type ManagedActivityLog = {
    id: number;
    event: string;
    description: string;
    actor: string | null;
    actorEmail: string | null;
    subjectType: string | null;
    subjectId: number | string | null;
    ipAddress: string | null;
    createdAt: string | null;
    properties: Record<string, unknown>;
};

export type ActivityLogFilters = {
    search: string;
    event: string;
};

export type SearchResultItem = {
    id: string;
    title: string;
    description: string;
    href: string;
    meta: string | null;
};

export type SearchResultGroup = {
    key: string;
    title: string;
    count: number;
    items: SearchResultItem[];
};

export type SearchFilters = {
    q: string;
};

export type ExportResource = {
    key: string;
    title: string;
    description: string;
    href: string;
    actionLabel: string;
    format: string;
};

export type PrintSummary = {
    counts: {
        users: number;
        roles: number;
        unreadNotifications: number;
        activityLogs: number;
    };
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        roles: string[];
        createdAt: string | null;
    }>;
    recentEvents: Array<{
        id: number;
        event: string;
        description: string;
        createdAt: string | null;
    }>;
};
