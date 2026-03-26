<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $employees = $this->filteredEmployees($search, $status);

        return Inertia::render('employees/Index', [
            'stats' => $this->indexStats($employees),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'employees' => $employees
                ->map(fn (array $employee): array => $this->indexEmployee($employee))
                ->values()
                ->all(),
        ]);
    }

    public function show(string $employee): Response
    {
        $employeeRecord = $this->employeeDirectory()
            ->firstWhere('id', (int) $employee);

        abort_if($employeeRecord === null, 404);

        return Inertia::render('employees/Show', [
            'employee' => $this->showEmployee($employeeRecord),
            'personalDetails' => $this->personalDetails($employeeRecord),
            'jobDetails' => $this->jobDetails($employeeRecord),
            'employmentHistory' => $this->employmentHistory($employeeRecord),
            'positionTracking' => $this->positionTracking($employeeRecord),
            'documents' => $this->documents($employeeRecord),
        ]);
    }

    private function filteredEmployees(string $search, string $status): Collection
    {
        $employees = $this->employeeDirectory();

        if ($search !== '') {
            $searchTerm = Str::lower($search);

            $employees = $employees->filter(function (array $employee) use ($searchTerm): bool {
                $searchableText = Str::lower(implode(' ', [
                    $employee['name'],
                    $employee['employee_number'],
                    $employee['department'],
                    $employee['position'],
                ]));

                return Str::contains($searchableText, $searchTerm);
            });
        }

        if ($status !== '') {
            $employees = $employees->where('status', $status);
        }

        return $employees->values();
    }

    private function indexStats(Collection $employees): array
    {
        $recentChangeCutoff = CarbonImmutable::now()->subDays(120);
        $employeesMissingDocuments = $employees
            ->filter(fn (array $employee): bool => $this->documentSummary($employee)['missing'] > 0)
            ->count();
        $recentRoleChanges = $employees
            ->filter(
                fn (array $employee): bool => CarbonImmutable::parse($employee['recent_change']['effective_date'])
                    ->greaterThanOrEqualTo($recentChangeCutoff)
            )
            ->count();

        return [
            [
                'label' => 'Total employees',
                'value' => $employees->count(),
                'description' => 'Profiles in the current directory view',
            ],
            [
                'label' => 'Active employees',
                'value' => $employees->where('status', 'Active')->count(),
                'description' => 'People currently in active service',
            ],
            [
                'label' => 'Missing documents',
                'value' => $employeesMissingDocuments,
                'description' => 'Profiles that still need contract or ID files',
            ],
            [
                'label' => 'Recent role changes',
                'value' => $recentRoleChanges,
                'description' => 'Moves recorded in the last 120 days',
            ],
        ];
    }

    private function indexEmployee(array $employee): array
    {
        $documentSummary = $this->documentSummary($employee);

        return [
            'id' => $employee['id'],
            'name' => $employee['name'],
            'employeeNumber' => $employee['employee_number'],
            'department' => $employee['department'],
            'position' => $employee['position'],
            'status' => $employee['status'],
            'workLocation' => $employee['work_location'],
            'documentStatus' => $documentSummary['label'],
            'missingDocuments' => $documentSummary['missing'],
            'recentChange' => $employee['recent_change']['title'],
            'recentChangeDate' => $this->formatDate($employee['recent_change']['effective_date']),
        ];
    }

    private function showEmployee(array $employee): array
    {
        $documentSummary = $this->documentSummary($employee);

        return [
            'id' => $employee['id'],
            'name' => $employee['name'],
            'employeeNumber' => $employee['employee_number'],
            'department' => $employee['department'],
            'position' => $employee['position'],
            'status' => $employee['status'],
            'workLocation' => $employee['work_location'],
            'documentStatus' => $documentSummary['label'],
            'manager' => $employee['manager'],
            'employmentType' => $employee['employment_type'],
            'hireDate' => $this->formatDate($employee['hire_date']),
            'recentChange' => $employee['recent_change']['title'],
            'recentChangeDate' => $this->formatDate($employee['recent_change']['effective_date']),
        ];
    }

    private function personalDetails(array $employee): array
    {
        return [
            [
                'label' => 'Legal name',
                'value' => $employee['name'],
            ],
            [
                'label' => 'Preferred name',
                'value' => $employee['preferred_name'],
            ],
            [
                'label' => 'Email',
                'value' => $employee['email'],
            ],
            [
                'label' => 'Phone',
                'value' => $employee['phone'],
            ],
            [
                'label' => 'Birth date',
                'value' => $this->formatDate($employee['birth_date']),
            ],
            [
                'label' => 'Address',
                'value' => $employee['address'],
            ],
            [
                'label' => 'Emergency contact',
                'value' => $employee['emergency_contact'],
            ],
        ];
    }

    private function jobDetails(array $employee): array
    {
        return [
            [
                'label' => 'Employee number',
                'value' => $employee['employee_number'],
            ],
            [
                'label' => 'Department',
                'value' => $employee['department'],
            ],
            [
                'label' => 'Position',
                'value' => $employee['position'],
            ],
            [
                'label' => 'Employment type',
                'value' => $employee['employment_type'],
            ],
            [
                'label' => 'Manager',
                'value' => $employee['manager'],
            ],
            [
                'label' => 'Hire date',
                'value' => $this->formatDate($employee['hire_date']),
            ],
            [
                'label' => 'Work location',
                'value' => $employee['work_location'],
            ],
            [
                'label' => 'Status',
                'value' => $employee['status'],
            ],
        ];
    }

    private function employmentHistory(array $employee): array
    {
        return collect($employee['employment_history'])
            ->map(fn (array $entry): array => [
                'id' => $entry['id'],
                'type' => $entry['type'],
                'title' => $entry['title'],
                'effectiveDate' => $this->formatDate($entry['effective_date']),
                'description' => $entry['description'],
                'badge' => $entry['badge'],
            ])
            ->values()
            ->all();
    }

    private function positionTracking(array $employee): array
    {
        return collect($employee['position_tracking'])
            ->map(fn (array $entry): array => [
                'id' => $entry['id'],
                'effectiveDate' => $this->formatDate($entry['effective_date']),
                'fromDepartment' => $entry['from_department'],
                'toDepartment' => $entry['to_department'],
                'fromPosition' => $entry['from_position'],
                'toPosition' => $entry['to_position'],
                'manager' => $entry['manager'],
                'reason' => $entry['reason'],
            ])
            ->values()
            ->all();
    }

    private function documents(array $employee): array
    {
        return [
            'contracts' => $this->formattedDocuments($employee['documents']['contracts']),
            'ids' => $this->formattedDocuments($employee['documents']['ids']),
        ];
    }

    private function formattedDocuments(array $documents): array
    {
        return collect($documents)
            ->map(fn (array $document): array => [
                'id' => $document['id'],
                'name' => $document['name'],
                'fileType' => $document['file_type'],
                'status' => $document['status'],
                'issuedOn' => $this->formatDate($document['issued_on']),
                'expiresOn' => $this->formatDate($document['expires_on']),
                'note' => $document['note'],
            ])
            ->values()
            ->all();
    }

    private function documentSummary(array $employee): array
    {
        $documents = collect($employee['documents']['contracts'])
            ->merge($employee['documents']['ids']);
        $missingCount = $documents->where('status', 'Missing')->count();
        $expiringSoonCount = $documents->where('status', 'Expiring Soon')->count();

        $label = match (true) {
            $missingCount > 0 => sprintf('%d missing', $missingCount),
            $expiringSoonCount > 0 => 'Expiring soon',
            default => 'Complete',
        };

        return [
            'label' => $label,
            'missing' => $missingCount,
            'expiringSoon' => $expiringSoonCount,
        ];
    }

    private function formatDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return CarbonImmutable::parse($date)->format('M d, Y');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function employeeDirectory(): Collection
    {
        return collect([
            [
                'id' => 1001,
                'employee_number' => 'EMP-1001',
                'name' => 'Mara Santos',
                'preferred_name' => 'Mara',
                'email' => 'mara.santos@example.test',
                'phone' => '+63 917 555 0142',
                'birth_date' => '1993-08-14',
                'address' => 'Quezon City, Metro Manila',
                'emergency_contact' => 'Luis Santos · Brother · +63 917 555 0199',
                'department' => 'People Operations',
                'position' => 'HR Generalist',
                'employment_type' => 'Full-time',
                'manager' => 'Alyssa Ramos',
                'hire_date' => '2022-03-14',
                'work_location' => 'Makati HQ',
                'status' => 'Active',
                'recent_change' => [
                    'title' => 'Promoted to HR Generalist',
                    'effective_date' => '2026-02-12',
                ],
                'employment_history' => [
                    [
                        'id' => 'mara-hire',
                        'type' => 'Hire',
                        'title' => 'Joined the company',
                        'effective_date' => '2022-03-14',
                        'description' => 'Started as an HR Associate supporting onboarding and records management.',
                        'badge' => 'Full-time',
                    ],
                    [
                        'id' => 'mara-regularization',
                        'type' => 'Regularization',
                        'title' => 'Completed probation',
                        'effective_date' => '2022-09-14',
                        'description' => 'Confirmed as a regular employee after six months of strong delivery.',
                        'badge' => 'People Ops',
                    ],
                    [
                        'id' => 'mara-transfer',
                        'type' => 'Transfer',
                        'title' => 'Moved into People Operations',
                        'effective_date' => '2024-06-03',
                        'description' => 'Shifted from Talent Support to People Operations to own lifecycle programs.',
                        'badge' => 'Department move',
                    ],
                    [
                        'id' => 'mara-promotion',
                        'type' => 'Promotion',
                        'title' => 'Promoted to HR Generalist',
                        'effective_date' => '2026-02-12',
                        'description' => 'Expanded scope to employee relations, policy rollouts, and HR operations.',
                        'badge' => 'Recent',
                    ],
                ],
                'position_tracking' => [
                    [
                        'id' => 'mara-track-1',
                        'effective_date' => '2024-06-03',
                        'from_department' => 'Talent Support',
                        'to_department' => 'People Operations',
                        'from_position' => 'Talent Support Associate',
                        'to_position' => 'HR Associate',
                        'manager' => 'Alyssa Ramos',
                        'reason' => 'Internal mobility to support HR operations growth.',
                    ],
                    [
                        'id' => 'mara-track-2',
                        'effective_date' => '2026-02-12',
                        'from_department' => 'People Operations',
                        'to_department' => 'People Operations',
                        'from_position' => 'HR Associate',
                        'to_position' => 'HR Generalist',
                        'manager' => 'Alyssa Ramos',
                        'reason' => 'Promotion after leading onboarding automation and compliance tracking.',
                    ],
                ],
                'documents' => [
                    'contracts' => [
                        [
                            'id' => 'mara-contract',
                            'name' => 'Signed employment contract',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2022-03-14',
                            'expires_on' => null,
                            'note' => 'Master signed copy is stored in the employee file.',
                        ],
                        [
                            'id' => 'mara-non-disclosure',
                            'name' => 'NDA and policy acknowledgment',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2022-03-14',
                            'expires_on' => null,
                            'note' => 'Includes signed handbook and confidentiality acknowledgment.',
                        ],
                    ],
                    'ids' => [
                        [
                            'id' => 'mara-national-id',
                            'name' => 'National ID',
                            'file_type' => 'JPEG',
                            'status' => 'Verified',
                            'issued_on' => '2021-11-04',
                            'expires_on' => null,
                            'note' => 'Front and back copy captured.',
                        ],
                        [
                            'id' => 'mara-tax-id',
                            'name' => 'Tax identification card',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2022-03-11',
                            'expires_on' => null,
                            'note' => 'Validated during onboarding.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 1002,
                'employee_number' => 'EMP-1002',
                'name' => 'Dianne Garcia',
                'preferred_name' => 'Di',
                'email' => 'dianne.garcia@example.test',
                'phone' => '+63 917 555 0188',
                'birth_date' => '1991-11-28',
                'address' => 'Pasig City, Metro Manila',
                'emergency_contact' => 'Marco Garcia · Husband · +63 917 555 0171',
                'department' => 'Finance',
                'position' => 'Payroll Specialist',
                'employment_type' => 'Full-time',
                'manager' => 'Paula Reyes',
                'hire_date' => '2021-07-05',
                'work_location' => 'BGC Office',
                'status' => 'Active',
                'recent_change' => [
                    'title' => 'Transferred into payroll operations',
                    'effective_date' => '2026-01-10',
                ],
                'employment_history' => [
                    [
                        'id' => 'dianne-hire',
                        'type' => 'Hire',
                        'title' => 'Joined finance shared services',
                        'effective_date' => '2021-07-05',
                        'description' => 'Started as an Accounting Assistant handling disbursement support.',
                        'badge' => 'Full-time',
                    ],
                    [
                        'id' => 'dianne-regularization',
                        'type' => 'Regularization',
                        'title' => 'Completed probation',
                        'effective_date' => '2022-01-05',
                        'description' => 'Moved into regular status after consistently closing monthly payroll cycles.',
                        'badge' => 'Finance',
                    ],
                    [
                        'id' => 'dianne-transfer',
                        'type' => 'Transfer',
                        'title' => 'Moved to payroll operations',
                        'effective_date' => '2026-01-10',
                        'description' => 'Took ownership of payroll processing for headquarters and field staff.',
                        'badge' => 'Recent',
                    ],
                ],
                'position_tracking' => [
                    [
                        'id' => 'dianne-track-1',
                        'effective_date' => '2023-08-01',
                        'from_department' => 'Finance',
                        'to_department' => 'Finance',
                        'from_position' => 'Accounting Assistant',
                        'to_position' => 'Finance Analyst',
                        'manager' => 'Paula Reyes',
                        'reason' => 'Expanded reporting responsibilities after process improvement work.',
                    ],
                    [
                        'id' => 'dianne-track-2',
                        'effective_date' => '2026-01-10',
                        'from_department' => 'Finance',
                        'to_department' => 'Finance',
                        'from_position' => 'Finance Analyst',
                        'to_position' => 'Payroll Specialist',
                        'manager' => 'Paula Reyes',
                        'reason' => 'Role aligned to payroll operations coverage.',
                    ],
                ],
                'documents' => [
                    'contracts' => [
                        [
                            'id' => 'dianne-contract',
                            'name' => 'Signed employment contract',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2021-07-05',
                            'expires_on' => null,
                            'note' => 'Original contract stored with finance onboarding packet.',
                        ],
                        [
                            'id' => 'dianne-payroll-addendum',
                            'name' => 'Payroll confidentiality addendum',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2026-01-08',
                            'expires_on' => null,
                            'note' => 'Signed ahead of the transfer to payroll operations.',
                        ],
                    ],
                    'ids' => [
                        [
                            'id' => 'dianne-tax-id',
                            'name' => 'Tax identification card',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2021-06-29',
                            'expires_on' => null,
                            'note' => 'Validated during onboarding.',
                        ],
                        [
                            'id' => 'dianne-health-card',
                            'name' => 'PhilHealth ID',
                            'file_type' => 'JPEG',
                            'status' => 'Missing',
                            'issued_on' => '2021-07-05',
                            'expires_on' => null,
                            'note' => 'Employee submitted details, but the scanned copy is still pending upload.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 1003,
                'employee_number' => 'EMP-1003',
                'name' => 'Noel Reyes',
                'preferred_name' => 'Noel',
                'email' => 'noel.reyes@example.test',
                'phone' => '+63 917 555 0156',
                'birth_date' => '1995-01-17',
                'address' => 'Taguig City, Metro Manila',
                'emergency_contact' => 'Carmen Reyes · Mother · +63 917 555 0123',
                'department' => 'Technology',
                'position' => 'Product Designer',
                'employment_type' => 'Full-time',
                'manager' => 'Lena Flores',
                'hire_date' => '2023-02-20',
                'work_location' => 'Hybrid',
                'status' => 'On Leave',
                'recent_change' => [
                    'title' => 'Shifted into the design systems pod',
                    'effective_date' => '2025-10-05',
                ],
                'employment_history' => [
                    [
                        'id' => 'noel-hire',
                        'type' => 'Hire',
                        'title' => 'Joined product design',
                        'effective_date' => '2023-02-20',
                        'description' => 'Started as a UI Designer supporting internal operations tools.',
                        'badge' => 'Full-time',
                    ],
                    [
                        'id' => 'noel-regularization',
                        'type' => 'Regularization',
                        'title' => 'Completed probation',
                        'effective_date' => '2023-08-20',
                        'description' => 'Regularized after delivering the first design system rollout.',
                        'badge' => 'Technology',
                    ],
                    [
                        'id' => 'noel-transfer',
                        'type' => 'Transfer',
                        'title' => 'Moved to design systems pod',
                        'effective_date' => '2025-10-05',
                        'description' => 'Focused on reusable UI standards across the HRIS product suite.',
                        'badge' => 'Internal move',
                    ],
                    [
                        'id' => 'noel-leave',
                        'type' => 'Status change',
                        'title' => 'Approved temporary leave',
                        'effective_date' => '2026-03-04',
                        'description' => 'Currently on a scheduled leave with return-to-work planning already set.',
                        'badge' => 'On leave',
                    ],
                ],
                'position_tracking' => [
                    [
                        'id' => 'noel-track-1',
                        'effective_date' => '2024-04-15',
                        'from_department' => 'Technology',
                        'to_department' => 'Technology',
                        'from_position' => 'UI Designer',
                        'to_position' => 'UX Designer',
                        'manager' => 'Lena Flores',
                        'reason' => 'Expanded into end-to-end user flows for HR products.',
                    ],
                    [
                        'id' => 'noel-track-2',
                        'effective_date' => '2025-10-05',
                        'from_department' => 'Technology',
                        'to_department' => 'Technology',
                        'from_position' => 'UX Designer',
                        'to_position' => 'Product Designer',
                        'manager' => 'Lena Flores',
                        'reason' => 'Aligned to design systems and product pattern ownership.',
                    ],
                ],
                'documents' => [
                    'contracts' => [
                        [
                            'id' => 'noel-contract',
                            'name' => 'Signed employment contract',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2023-02-20',
                            'expires_on' => null,
                            'note' => 'Master copy is already archived.',
                        ],
                        [
                            'id' => 'noel-hybrid-addendum',
                            'name' => 'Hybrid work agreement',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2024-04-15',
                            'expires_on' => null,
                            'note' => 'Updated when the product team moved to hybrid scheduling.',
                        ],
                    ],
                    'ids' => [
                        [
                            'id' => 'noel-driver-license',
                            'name' => 'Driver’s license',
                            'file_type' => 'JPEG',
                            'status' => 'Expiring Soon',
                            'issued_on' => '2022-06-12',
                            'expires_on' => '2026-06-12',
                            'note' => 'Needs a refreshed copy before the recorded expiry date.',
                        ],
                        [
                            'id' => 'noel-tax-id',
                            'name' => 'Tax identification card',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2023-02-15',
                            'expires_on' => null,
                            'note' => 'Validated during onboarding.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 1004,
                'employee_number' => 'EMP-1004',
                'name' => 'Bea Mendoza',
                'preferred_name' => 'Bea',
                'email' => 'bea.mendoza@example.test',
                'phone' => '+63 917 555 0134',
                'birth_date' => '1998-05-06',
                'address' => 'Cainta, Rizal',
                'emergency_contact' => 'Ruben Mendoza · Father · +63 917 555 0166',
                'department' => 'Operations',
                'position' => 'Warehouse Supervisor',
                'employment_type' => 'Probationary',
                'manager' => 'Carlo Sison',
                'hire_date' => '2025-12-02',
                'work_location' => 'Laguna Distribution Hub',
                'status' => 'Probationary',
                'recent_change' => [
                    'title' => 'Promoted to warehouse supervisor',
                    'effective_date' => '2026-03-01',
                ],
                'employment_history' => [
                    [
                        'id' => 'bea-hire',
                        'type' => 'Hire',
                        'title' => 'Joined warehouse operations',
                        'effective_date' => '2025-12-02',
                        'description' => 'Started as a Warehouse Coordinator for inbound receiving.',
                        'badge' => 'Probationary',
                    ],
                    [
                        'id' => 'bea-promotion',
                        'type' => 'Promotion',
                        'title' => 'Promoted to warehouse supervisor',
                        'effective_date' => '2026-03-01',
                        'description' => 'Now leading shift scheduling, safety checks, and inventory handoffs.',
                        'badge' => 'Recent',
                    ],
                ],
                'position_tracking' => [
                    [
                        'id' => 'bea-track-1',
                        'effective_date' => '2026-03-01',
                        'from_department' => 'Operations',
                        'to_department' => 'Operations',
                        'from_position' => 'Warehouse Coordinator',
                        'to_position' => 'Warehouse Supervisor',
                        'manager' => 'Carlo Sison',
                        'reason' => 'Stepped into a team-lead role after peak-season performance.',
                    ],
                ],
                'documents' => [
                    'contracts' => [
                        [
                            'id' => 'bea-offer',
                            'name' => 'Signed offer and employment contract',
                            'file_type' => 'PDF',
                            'status' => 'Verified',
                            'issued_on' => '2025-12-02',
                            'expires_on' => null,
                            'note' => 'Offer acceptance and contract packet are on file.',
                        ],
                        [
                            'id' => 'bea-probation-plan',
                            'name' => 'Probation review plan',
                            'file_type' => 'PDF',
                            'status' => 'Missing',
                            'issued_on' => '2025-12-15',
                            'expires_on' => null,
                            'note' => 'Manager completed the review plan, but the signed copy is not uploaded yet.',
                        ],
                    ],
                    'ids' => [
                        [
                            'id' => 'bea-national-id',
                            'name' => 'National ID',
                            'file_type' => 'JPEG',
                            'status' => 'Verified',
                            'issued_on' => '2021-09-09',
                            'expires_on' => null,
                            'note' => 'Front and back copy captured.',
                        ],
                        [
                            'id' => 'bea-sss-id',
                            'name' => 'SSS identification card',
                            'file_type' => 'JPEG',
                            'status' => 'Missing',
                            'issued_on' => '2025-12-02',
                            'expires_on' => null,
                            'note' => 'Awaiting scan after government ID enrollment.',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
