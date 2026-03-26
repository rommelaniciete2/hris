<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BriefcaseBusiness,
    Building2,
    Calendar,
    FileText,
    MapPin,
    Upload,
    UserRound,
} from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as employeesIndex, show as employeeShow } from '@/routes/employees';
import type { BreadcrumbItem, EmployeeShowPageProps } from '@/types';

const props = defineProps<EmployeeShowPageProps>();

const { getInitials } = useInitials();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee Management',
        href: employeesIndex(),
    },
    {
        title: props.employee.name,
        href: employeeShow(props.employee.id),
    },
];

const sections = [
    {
        id: 'profile',
        title: 'Profile',
        icon: UserRound,
    },
    {
        id: 'history',
        title: 'Employment history',
        icon: Calendar,
    },
    {
        id: 'tracking',
        title: 'Department & position',
        icon: Building2,
    },
    {
        id: 'documents',
        title: 'Documents',
        icon: FileText,
    },
];

const statusClass = (status: string): string => {
    if (status === 'Active') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'Probationary') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300';
};

const documentStatusClass = (status: string): string => {
    if (status === 'Complete') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'Expiring soon') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300';
};

const documentItemStatusClass = (status: string): string => {
    if (status === 'Verified') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'Expiring Soon') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300';
};
</script>

<template>
    <Head :title="`${employee.name} · Employee Management`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <Card
                class="overflow-hidden border-border/70 bg-gradient-to-br from-background via-background to-muted/45"
            >
                <CardContent class="grid gap-6 px-6 py-6 lg:grid-cols-[auto_minmax(0,1fr)]">
                    <Avatar
                        class="size-20 rounded-2xl border border-border/70 bg-muted/70"
                    >
                        <AvatarFallback class="rounded-2xl text-lg font-semibold">
                            {{ getInitials(employee.name) }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    variant="outline"
                                    :class="statusClass(employee.status)"
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

                            <div class="space-y-2">
                                <p class="text-xs font-medium tracking-[0.18em] text-muted-foreground uppercase">
                                    {{ employee.employeeNumber }}
                                </p>
                                <h1 class="text-3xl font-semibold tracking-tight">
                                    {{ employee.name }}
                                </h1>
                                <p class="text-sm leading-6 text-muted-foreground">
                                    {{ employee.department }} · {{ employee.position }} ·
                                    {{ employee.employmentType }} under
                                    {{ employee.manager }}
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                                    <p class="mb-2 text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                        Hire date
                                    </p>
                                    <p class="text-sm font-medium">{{ employee.hireDate }}</p>
                                </div>
                                <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                                    <p class="mb-2 text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                        Manager
                                    </p>
                                    <p class="text-sm font-medium">{{ employee.manager }}</p>
                                </div>
                                <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                                    <p class="mb-2 text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                        Work location
                                    </p>
                                    <p class="text-sm font-medium">{{ employee.workLocation }}</p>
                                </div>
                                <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                                    <p class="mb-2 text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                        Latest movement
                                    </p>
                                    <p class="text-sm font-medium">{{ employee.recentChange }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ employee.recentChangeDate }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                            <Button variant="outline" as-child>
                                <Link :href="employeesIndex()">
                                    <ArrowLeft />
                                    Back to directory
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="overflow-x-auto lg:hidden">
                <div class="flex gap-2 pb-1">
                    <Button
                        v-for="section in sections"
                        :key="section.id"
                        variant="outline"
                        class="shrink-0"
                        as-child
                    >
                        <a :href="`#${section.id}`">
                            <component :is="section.icon" />
                            {{ section.title }}
                        </a>
                    </Button>
                </div>
            </div>

            <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                <aside class="hidden w-full max-w-xs lg:block xl:max-w-sm">
                    <Card class="sticky top-6 border-border/70">
                        <CardHeader>
                            <CardTitle class="text-lg">Sections</CardTitle>
                            <CardDescription>
                                Jump between profile, history, tracking, and document storage.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <nav class="flex flex-col space-y-1">
                                <Button
                                    v-for="section in sections"
                                    :key="section.id"
                                    variant="ghost"
                                    class="w-full justify-start"
                                    as-child
                                >
                                    <a :href="`#${section.id}`">
                                        <component :is="section.icon" />
                                        {{ section.title }}
                                    </a>
                                </Button>
                            </nav>
                        </CardContent>
                    </Card>
                </aside>

                <div class="min-w-0 flex-1 space-y-6">
                    <section id="profile" class="scroll-mt-24 space-y-4">
                        <Heading
                            title="Profile"
                            description="Personal and job details surfaced in the employee profile workspace."
                        />

                        <div class="grid gap-4 xl:grid-cols-2">
                            <Card class="border-border/70">
                                <CardHeader>
                                    <CardTitle>Personal details</CardTitle>
                                    <CardDescription>
                                        Primary identity and contact information.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <dl class="grid gap-4">
                                        <div
                                            v-for="detail in personalDetails"
                                            :key="detail.label"
                                            class="grid gap-1 border-b border-border/70 pb-4 last:border-b-0 last:pb-0"
                                        >
                                            <dt class="text-sm font-medium text-muted-foreground">
                                                {{ detail.label }}
                                            </dt>
                                            <dd class="text-sm leading-6">
                                                {{ detail.value }}
                                            </dd>
                                        </div>
                                    </dl>
                                </CardContent>
                            </Card>

                            <Card class="border-border/70">
                                <CardHeader>
                                    <CardTitle>Job details</CardTitle>
                                    <CardDescription>
                                        Employment placement and reporting information.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <dl class="grid gap-4">
                                        <div
                                            v-for="detail in jobDetails"
                                            :key="detail.label"
                                            class="grid gap-1 border-b border-border/70 pb-4 last:border-b-0 last:pb-0"
                                        >
                                            <dt class="text-sm font-medium text-muted-foreground">
                                                {{ detail.label }}
                                            </dt>
                                            <dd class="text-sm leading-6">
                                                {{ detail.value }}
                                            </dd>
                                        </div>
                                    </dl>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <section id="history" class="scroll-mt-24 space-y-4">
                        <Heading
                            title="Employment History"
                            description="A chronological view of key employment milestones and status changes."
                        />

                        <Card class="border-border/70">
                            <CardContent class="px-6 py-6">
                                <ol class="space-y-6">
                                    <li
                                        v-for="entry in employmentHistory"
                                        :key="entry.id"
                                        class="relative pl-6"
                                    >
                                        <span
                                            class="absolute top-1.5 left-0 size-2.5 rounded-full bg-primary"
                                        />
                                        <span
                                            class="absolute top-4 left-[4px] h-[calc(100%-0.25rem)] w-px bg-border last:hidden"
                                        />

                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    {{ entry.type }}
                                                </p>
                                                <h3 class="text-base font-semibold">
                                                    {{ entry.title }}
                                                </h3>
                                                <p class="max-w-3xl text-sm leading-6 text-muted-foreground">
                                                    {{ entry.description }}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <Badge variant="outline">
                                                    {{ entry.badge }}
                                                </Badge>
                                                <span class="text-sm text-muted-foreground">
                                                    {{ entry.effectiveDate }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                </ol>
                            </CardContent>
                        </Card>
                    </section>

                    <section id="tracking" class="scroll-mt-24 space-y-4">
                        <Heading
                            title="Department & Position Tracking"
                            description="Recorded changes in department, role, manager, and movement reason."
                        />

                        <Card class="border-border/70">
                            <CardContent class="grid gap-4 px-6 py-6">
                                <div
                                    v-for="entry in positionTracking"
                                    :key="entry.id"
                                    class="rounded-xl border border-border/70 bg-muted/30 p-5"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-2">
                                            <p class="text-sm font-semibold">
                                                {{ entry.toDepartment }} · {{ entry.toPosition }}
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                From {{ entry.fromDepartment }} · {{ entry.fromPosition }}
                                            </p>
                                        </div>
                                        <span class="text-sm text-muted-foreground">
                                            {{ entry.effectiveDate }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 text-sm text-muted-foreground md:grid-cols-2">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="size-4" />
                                            <span>Manager: {{ entry.manager }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <BriefcaseBusiness class="size-4" />
                                            <span>{{ entry.reason }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <section id="documents" class="scroll-mt-24 space-y-4">
                        <Heading
                            title="Document Storage"
                            description="Contract and identification records with placeholder upload affordances for the next phase."
                        />

                        <div class="grid gap-4 xl:grid-cols-2">
                            <Card class="border-border/70">
                                <CardHeader class="gap-4 sm:flex sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-1.5">
                                        <CardTitle>Contracts</CardTitle>
                                        <CardDescription>
                                            Employment agreements, acknowledgments, and probation-related files.
                                        </CardDescription>
                                    </div>
                                    <Button variant="outline" size="sm" disabled>
                                        <Upload />
                                        Upload upcoming
                                    </Button>
                                </CardHeader>
                                <CardContent class="grid gap-4">
                                    <div
                                        v-for="document in documents.contracts"
                                        :key="document.id"
                                        class="rounded-xl border border-border/70 bg-muted/20 p-4"
                                    >
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <h3 class="font-medium">{{ document.name }}</h3>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ document.fileType }}
                                                </p>
                                            </div>
                                            <Badge
                                                variant="outline"
                                                :class="documentItemStatusClass(document.status)"
                                            >
                                                {{ document.status }}
                                            </Badge>
                                        </div>

                                        <div class="mt-4 grid gap-3 text-sm text-muted-foreground">
                                            <div class="flex items-center gap-2">
                                                <Calendar class="size-4" />
                                                <span>Issued {{ document.issuedOn }}</span>
                                            </div>
                                            <p>{{ document.note }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="border-border/70">
                                <CardHeader class="gap-4 sm:flex sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-1.5">
                                        <CardTitle>IDs</CardTitle>
                                        <CardDescription>
                                            Government and payroll identification files captured for employment records.
                                        </CardDescription>
                                    </div>
                                    <Button variant="outline" size="sm" disabled>
                                        <Upload />
                                        Upload upcoming
                                    </Button>
                                </CardHeader>
                                <CardContent class="grid gap-4">
                                    <div
                                        v-for="document in documents.ids"
                                        :key="document.id"
                                        class="rounded-xl border border-border/70 bg-muted/20 p-4"
                                    >
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <h3 class="font-medium">{{ document.name }}</h3>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ document.fileType }}
                                                </p>
                                            </div>
                                            <Badge
                                                variant="outline"
                                                :class="documentItemStatusClass(document.status)"
                                            >
                                                {{ document.status }}
                                            </Badge>
                                        </div>

                                        <div class="mt-4 grid gap-3 text-sm text-muted-foreground">
                                            <div class="flex items-center gap-2">
                                                <Calendar class="size-4" />
                                                <span>Issued {{ document.issuedOn }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <MapPin class="size-4" />
                                                <span>
                                                    {{
                                                        document.expiresOn
                                                            ? `Expires ${document.expiresOn}`
                                                            : 'No expiry recorded'
                                                    }}
                                                </span>
                                            </div>
                                            <p>{{ document.note }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
