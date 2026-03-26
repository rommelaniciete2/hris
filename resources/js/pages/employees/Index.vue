<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    BriefcaseBusiness,
    FileText,
    MapPin,
    Search,
    Users,
} from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as employeesIndex, show as employeeShow } from '@/routes/employees';
import type {
    BreadcrumbItem,
    EmployeeDirectoryItem,
    EmployeeIndexPageProps,
} from '@/types';

const props = defineProps<EmployeeIndexPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee Management',
        href: employeesIndex(),
    },
];

const filterForm = reactive({
    search: props.filters.search,
    status: props.filters.status || 'all',
});

const statsIcons = [Users, BriefcaseBusiness, FileText, ArrowRight];
const statusOptions = ['Active', 'Probationary', 'On Leave'];

const hasAppliedFilters = computed(
    () => props.filters.search !== '' || props.filters.status !== '',
);

const applyFilters = (): void => {
    const query: Record<string, string> = {};

    if (filterForm.search.trim() !== '') {
        query.search = filterForm.search.trim();
    }

    if (filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    router.get(employeesIndex.url(), query, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const resetFilters = (): void => {
    filterForm.search = '';
    filterForm.status = 'all';

    router.get(employeesIndex.url(), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const employeeStatusClass = (status: EmployeeDirectoryItem['status']): string => {
    if (status === 'Active') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'Probationary') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300';
};

const documentStatusClass = (status: EmployeeDirectoryItem['documentStatus']): string => {
    if (status === 'Complete') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'Expiring soon') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300';
};
</script>

<template>
    <Head title="Employee Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <Card
                    class="border-border/70 bg-gradient-to-br from-background via-background to-muted/45"
                >
                    <CardHeader class="gap-4 sm:flex sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-3">
                            <Badge
                                variant="outline"
                                class="border-primary/20 bg-primary/5 text-primary"
                            >
                                HR workspace
                            </Badge>
                            <div class="space-y-2">
                                <CardTitle class="text-2xl tracking-tight">
                                    Employee management
                                </CardTitle>
                                <CardDescription class="max-w-2xl text-sm leading-6">
                                    Browse employee profiles, review movement across
                                    departments and positions, and spot document gaps
                                    before persistence is wired in.
                                </CardDescription>
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-border/70 bg-background/80 px-4 py-3 text-sm text-muted-foreground"
                        >
                            <p class="font-medium text-foreground">
                                {{ employees.length }} result<span v-if="employees.length !== 1">s</span>
                            </p>
                            <p>Current directory view</p>
                        </div>
                    </CardHeader>

                    <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div
                            v-for="(stat, index) in stats"
                            :key="stat.label"
                            class="rounded-xl border border-border/70 bg-card/80 p-4 shadow-sm"
                        >
                            <div class="mb-6 flex items-start justify-between gap-3">
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ stat.label }}
                                </p>
                                <component
                                    :is="statsIcons[index]"
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                            <p class="text-3xl font-semibold tracking-tight">
                                {{ stat.value }}
                            </p>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ stat.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/70">
                    <CardHeader>
                        <CardTitle class="text-lg">Filter the directory</CardTitle>
                        <CardDescription>
                            Search by name, employee number, department, or position.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form class="space-y-4" @submit.prevent="applyFilters">
                            <div class="grid gap-2">
                                <Label for="employee-search">Search</Label>
                                <div class="relative">
                                    <Search
                                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    />
                                    <Input
                                        id="employee-search"
                                        v-model="filterForm.search"
                                        class="pl-9"
                                        placeholder="e.g. Mara, Finance, EMP-1002"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="employee-status">Status</Label>
                                <Select v-model="filterForm.status">
                                    <SelectTrigger id="employee-status" class="w-full">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All statuses</SelectItem>
                                        <SelectItem
                                            v-for="status in statusOptions"
                                            :key="status"
                                            :value="status"
                                        >
                                            {{ status }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Button type="submit">Apply filters</Button>
                                <Button
                                    v-if="hasAppliedFilters"
                                    type="button"
                                    variant="outline"
                                    @click="resetFilters"
                                >
                                    Reset
                                </Button>
                            </div>

                            <div
                                v-if="hasAppliedFilters"
                                class="rounded-xl border border-dashed border-border/80 bg-muted/50 p-3 text-sm text-muted-foreground"
                            >
                                Viewing a filtered directory based on the current search
                                and status selection.
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </section>

            <section class="space-y-4">
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                >
                    <Heading
                        title="Employee Directory"
                        description="Open an employee record to review profile, history, tracking, and documents."
                    />
                    <p class="text-sm text-muted-foreground">
                        {{ employees.length }} employee<span v-if="employees.length !== 1">s</span>
                        visible
                    </p>
                </div>

                <div v-if="employees.length > 0" class="grid gap-4">
                    <Link
                        v-for="employee in employees"
                        :key="employee.id"
                        :href="employeeShow(employee.id)"
                        prefetch
                        class="block"
                    >
                        <Card
                            class="border-border/70 transition-transform duration-200 hover:-translate-y-0.5 hover:border-foreground/15 hover:shadow-md"
                        >
                            <CardContent class="grid gap-5 px-6 py-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                                <div class="space-y-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium tracking-[0.18em] text-muted-foreground uppercase">
                                                {{ employee.employeeNumber }}
                                            </p>
                                            <h3 class="text-xl font-semibold tracking-tight">
                                                {{ employee.name }}
                                            </h3>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <Badge
                                                variant="outline"
                                                :class="employeeStatusClass(employee.status)"
                                            >
                                                {{ employee.status }}
                                            </Badge>
                                            <Badge
                                                variant="outline"
                                                :class="documentStatusClass(employee.documentStatus)"
                                            >
                                                {{ employee.documentStatus }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 text-sm text-muted-foreground sm:grid-cols-2 xl:grid-cols-3">
                                        <div class="flex items-center gap-2">
                                            <BriefcaseBusiness class="size-4" />
                                            <span>{{ employee.department }} · {{ employee.position }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <MapPin class="size-4" />
                                            <span>{{ employee.workLocation }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <FileText class="size-4" />
                                            <span>
                                                {{
                                                    employee.missingDocuments > 0
                                                        ? `${employee.missingDocuments} missing file(s)`
                                                        : 'Documents look ready'
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-xl border border-dashed border-border/80 bg-muted/40 px-4 py-3 text-sm"
                                    >
                                        <p class="font-medium text-foreground">
                                            {{ employee.recentChange }}
                                        </p>
                                        <p class="text-muted-foreground">
                                            Recorded {{ employee.recentChangeDate }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-sm font-medium text-primary">
                                    <span>Open profile workspace</span>
                                    <ArrowRight class="size-4" />
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <Card
                    v-else
                    class="border-dashed border-border/80 bg-muted/20"
                >
                    <CardContent class="flex flex-col items-center gap-4 px-6 py-12 text-center">
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold">No employees match this filter</h3>
                            <p class="max-w-md text-sm text-muted-foreground">
                                Try adjusting the search text or resetting the status filter
                                to see the full employee directory again.
                            </p>
                        </div>

                        <Button variant="outline" @click="resetFilters">
                            Reset filters
                        </Button>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
