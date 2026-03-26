export type EmployeeIndexStat = {
    label: string;
    value: number;
    description: string;
};

export type EmployeeFilters = {
    search: string;
    status: string;
};

export type EmployeeDirectoryItem = {
    id: number;
    name: string;
    employeeNumber: string;
    department: string;
    position: string;
    status: string;
    workLocation: string;
    documentStatus: string;
    missingDocuments: number;
    recentChange: string;
    recentChangeDate: string;
};

export type EmployeeDetailField = {
    label: string;
    value: string;
};

export type EmployeeTimelineItem = {
    id: string;
    type: string;
    title: string;
    effectiveDate: string;
    description: string;
    badge: string;
};

export type EmployeeTrackingItem = {
    id: string;
    effectiveDate: string;
    fromDepartment: string;
    toDepartment: string;
    fromPosition: string;
    toPosition: string;
    manager: string;
    reason: string;
};

export type EmployeeDocumentItem = {
    id: string;
    name: string;
    fileType: string;
    status: string;
    issuedOn: string;
    expiresOn: string | null;
    note: string;
};

export type EmployeeDocuments = {
    contracts: EmployeeDocumentItem[];
    ids: EmployeeDocumentItem[];
};

export type EmployeeDetailSummary = {
    id: number;
    name: string;
    employeeNumber: string;
    department: string;
    position: string;
    status: string;
    workLocation: string;
    documentStatus: string;
    manager: string;
    employmentType: string;
    hireDate: string;
    recentChange: string;
    recentChangeDate: string;
};

export type EmployeeIndexPageProps = {
    stats: EmployeeIndexStat[];
    filters: EmployeeFilters;
    employees: EmployeeDirectoryItem[];
};

export type EmployeeShowPageProps = {
    employee: EmployeeDetailSummary;
    personalDetails: EmployeeDetailField[];
    jobDetails: EmployeeDetailField[];
    employmentHistory: EmployeeTimelineItem[];
    positionTracking: EmployeeTrackingItem[];
    documents: EmployeeDocuments;
};
