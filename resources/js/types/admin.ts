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

export type ManagedApplicantSocialLink = {
    platform: string;
    url: string;
    isVerified?: boolean;
};

export type ManagedApplicant = {
    id: number;
    userId: number;
    linkedUserName: string | null;
    linkedUserEmail: string | null;
    fullName: string;
    email: string;
    phone: string | null;
    applicantType: string;
    applicantTypeLabel: string;
    socialLinksCount: number;
    createdAt: string | null;
    bio?: string | null;
    nationalIdOrRegistrationRef?: string | null;
    socialLinks?: ManagedApplicantSocialLink[];
};

export type ManagedTeamMember = {
    id: number | null;
    applicantId: number | null;
    fullName: string;
    roleTitle: string;
    email: string | null;
    phone: string | null;
    bio: string | null;
    isPrimaryContact: boolean;
    linkedApplicantName?: string | null;
};

export type ManagedOrganization = {
    id?: number;
    legalName: string;
    displayName: string;
    registrationNumber: string | null;
    industryId?: number | null;
    industryName?: string | null;
    website: string | null;
    description?: string | null;
    contactEmail: string | null;
    contactPhone: string | null;
    address?: string | null;
    logoFileName?: string | null;
    logoDownloadUrl?: string | null;
    teamMembersCount?: number;
    primaryContactName?: string | null;
    createdAt?: string | null;
    teamMembers: ManagedTeamMember[];
};

export type ManagedSubmission = {
    id: number;
    title: string;
    seasonName: string | null;
    stageName: string | null;
    industryName: string | null;
    organizationName: string | null;
    applicantName?: string | null;
    applicantEmail?: string | null;
    status: string;
    statusLabel: string;
    statusTone: string;
    submittedAt: string | null;
    updatedAt: string | null;
    canEdit?: boolean;
    summary?: string | null;
    problemStatement?: string | null;
    solutionDescription?: string | null;
    businessModel?: string | null;
    seasonId?: number;
    currentStageId?: number | null;
    industryId?: number | null;
    organizationId?: number | null;
    isPublicAfterApproval?: boolean;
    currentVersionNumber?: number | null;
    versionCount?: number;
    latestStatusReason?: string | null;
    intakeNotes?: SubmissionIntakeNote[];
    intakeChecklist?: SubmissionIntakeChecklist;
    availableTransitions?: SubmissionTransitionOption[];
    draftFiles?: ManagedSubmissionFile[];
    currentVersionFiles?: ManagedSubmissionFile[];
    versionHistory?: SubmissionVersionEntry[];
    statusTimeline?: SubmissionStatusTimelineEntry[];
    reviewerAssignments?: ManagedReviewerAssignment[];
    screeningState?: string;
    screeningStateLabel?: string;
    assignedReviewersCount?: number;
    pendingReviewsCount?: number;
    submittedReviewsCount?: number;
    latestRecommendation?: string | null;
    latestRecommendationLabel?: string | null;
    technicalState?: string;
    technicalStateLabel?: string;
    assignedTechnicalReviewersCount?: number;
    submittedTechnicalReviewsCount?: number;
    screeningReviewsCount?: number;
    latestTechnicalRecommendationLabel?: string | null;
    averageTechnicalScore?: number | null;
    technicalAssignments?: ManagedReviewerAssignment[];
    screeningReviews?: {
        reviewerName: string | null;
        recommendation: string | null;
        recommendationLabel?: string | null;
        eligibilityStatus?: string | null;
        scoreOptional?: number | null;
        notes: string | null;
        submittedAt: string | null;
    }[];
    reviewDecisions?: ManagedReviewDecision[];
};

export type ManagedReviewer = {
    id: number;
    userId: number;
    name: string | null;
    email: string | null;
    professionalTitle: string | null;
    organization: string | null;
    specialization: string | null;
    bio: string | null;
    isActive: boolean;
    activeAssignmentsCount: number;
};

export type ManagedReviewDecision = {
    id: number;
    decisionType: string;
    decisionLabel: string;
    decisionReason: string | null;
    decidedAt: string | null;
    decidedBy: string | null;
};

export type ManagedShortlistRecord = {
    id: number;
    submissionId: number;
    submissionTitle: string | null;
    seasonName: string | null;
    stageName: string | null;
    applicantName: string | null;
    organizationName: string | null;
    rankOrderOptional: number | null;
    notes: string | null;
    approvalStatus: string;
    approvalStatusLabel: string;
    createdBy: string | null;
    approvedBy: string | null;
    approvedAt: string | null;
    exportedAt: string | null;
    createdAt: string | null;
};

export type ManagedReviewerAssignment = {
    id: number;
    assignmentType?: string;
    assignmentTypeLabel?: string;
    submissionId?: number;
    title?: string | null;
    seasonName?: string | null;
    stageName?: string | null;
    industryName?: string | null;
    applicantName?: string | null;
    reviewerName?: string | null;
    reviewerEmail?: string | null;
    status: string;
    statusLabel: string;
    statusTone: string;
    dueAt: string | null;
    assignedAt: string | null;
    isOverdue?: boolean;
    recommendationLabel?: string | null;
    review?: {
        eligibilityStatus: string | null;
        eligibilityChecklist?: Record<string, boolean> | null;
        recommendation: string | null;
        scoreOptional: number | null;
        notes: string | null;
        submittedAt: string | null;
        innovationScoreOptional?: number | null;
        feasibilityScoreOptional?: number | null;
        executionScoreOptional?: number | null;
        marketScoreOptional?: number | null;
        strengths?: string | null;
        weaknesses?: string | null;
        riskNote?: string | null;
        recommendationLabel?: string | null;
    } | null;
};

export type SubmissionIntakeChecklist = {
    items: SubmissionIntakeChecklistItem[];
    passedCount: number;
    totalCount: number;
    isReady: boolean;
};

export type SubmissionIntakeChecklistItem = {
    key: string;
    label: string;
    passed: boolean;
};

export type SubmissionIntakeNote = {
    statusLabel: string;
    reason: string | null;
    changedAt: string | null;
    changedBy: string | null;
};

export type SubmissionVersionEntry = {
    id: number;
    versionNo: number;
    changeNote: string | null;
    createdAt: string | null;
    createdBy: string | null;
    isLocked: boolean;
    isCurrent: boolean;
    snapshotTitle: string;
    snapshotStatus: string;
};

export type SubmissionFileDefinition = {
    type: string;
    label: string;
    description: string;
    required: boolean;
    multiple: boolean;
    accept: string;
};

export type ManagedSubmissionFile = {
    id: number;
    fileType: string;
    fileTypeLabel: string;
    originalName: string;
    mimeType: string | null;
    fileSize: number;
    description: string | null;
    downloadUrl: string;
    isRequired: boolean;
    isVerified: boolean;
    uploadedAt: string | null;
    uploadedBy: string | null;
    versionNumber: number | null;
};

export type SubmissionStatusTimelineEntry = {
    id: number;
    fromStatus: string | null;
    fromStatusLabel: string | null;
    toStatus: string;
    toStatusLabel: string;
    toStatusTone: string;
    reason: string | null;
    changedAt: string | null;
    changedBy: string | null;
};

export type SubmissionTransitionOption = {
    value: string;
    label: string;
    requiresReason: boolean;
};

export type SubmissionStageOption = {
    value: number;
    label: string;
    seasonId: number;
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
    seasonId?: string;
    stageId?: string;
    industryId?: string;
    status?: string;
    reviewerId?: string;
    recommendation?: string;
    queueState?: string;
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
