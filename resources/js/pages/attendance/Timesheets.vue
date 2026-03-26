<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock3, NotebookPen, TimerReset } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';
import AttendanceController from '@/actions/App/Http/Controllers/AttendanceController';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as attendanceIndex } from '@/routes/attendance';
import type { AttendanceTimesheetEntry, AttendanceTimesheetsPageProps, BreadcrumbItem } from '@/types';

type TimesheetDraft = {
    timesheet_start_at: string;
    timesheet_end_at: string;
    timesheet_notes: string;
};

const props = defineProps<AttendanceTimesheetsPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: attendanceIndex() },
    { title: 'Timesheets', href: AttendanceController.timesheets() },
];

const monthFilter = ref(props.filters.month);
const drafts = reactive<Record<number, TimesheetDraft>>({});
const rowErrors = reactive<Record<number, Record<string, string>>>({});
const savingEntryId = ref<number | null>(null);
const savedEntryId = ref<number | null>(null);
const textareaClass = 'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50';

watch(
    () => props.filters.month,
    (value) => {
        monthFilter.value = value;
    },
    { immediate: true },
);

watch(
    () => props.entries,
    (entries) => {
        seedDrafts(entries);
    },
    { immediate: true },
);

const badgeClass = (status: string): string => {
    if (status === 'Complete') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'Open') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-border/70 bg-muted/40 text-muted-foreground';
};

const metricClass = (value: number): string => {
    return value === 0
        ? 'border-border/70 bg-muted/30 text-muted-foreground'
        : 'border-primary/20 bg-primary/5 text-primary';
};

const applyMonthFilter = (): void => {
    router.get(AttendanceController.timesheets.url(), { month: monthFilter.value }, {
        preserveScroll: true,
        replace: true,
    });
};

const submitTimesheet = (entryId: number): void => {
    savingEntryId.value = entryId;
    savedEntryId.value = null;
    rowErrors[entryId] = {};

    router.patch(AttendanceController.updateTimesheet.url(entryId), drafts[entryId], {
        preserveScroll: true,
        onError: (errors) => {
            rowErrors[entryId] = errors as Record<string, string>;
        },
        onSuccess: () => {
            savedEntryId.value = entryId;
            window.setTimeout(() => {
                if (savedEntryId.value === entryId) {
                    savedEntryId.value = null;
                }
            }, 2000);
        },
        onFinish: () => {
            savingEntryId.value = null;
        },
    });
};

const errorsForEntry = (entryId: number): string[] => {
    return Object.values(rowErrors[entryId] ?? {}).filter(Boolean);
};

function seedDrafts(entries: AttendanceTimesheetEntry[]): void {
    Object.keys(drafts).forEach((key) => delete drafts[Number(key)]);
    Object.keys(rowErrors).forEach((key) => delete rowErrors[Number(key)]);

    entries.forEach((entry) => {
        drafts[entry.id] = {
            timesheet_start_at: entry.editableStartAt,
            timesheet_end_at: entry.editableEndAt,
            timesheet_notes: entry.timesheetNotes ?? '',
        };
    });
}
</script>

<template>
    <Head title="Attendance Timesheets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <section class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <Card class="border-border/70 bg-gradient-to-br from-background via-background to-muted/45">
                    <CardHeader class="gap-4 sm:flex sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <Badge variant="outline" class="border-primary/20 bg-primary/5 text-primary">Editable timesheets</Badge>
                            <CardTitle class="text-2xl tracking-tight">Timesheets</CardTitle>
                            <CardDescription class="max-w-2xl text-sm leading-6">
                                Review raw punches, adjust effective times, and keep notes without deleting the original log.
                            </CardDescription>
                        </div>

                        <Button variant="outline" as-child>
                            <Link :href="attendanceIndex()">Back to attendance</Link>
                        </Button>
                    </CardHeader>
                </Card>

                <Card class="border-border/70">
                    <CardHeader>
                        <CardTitle class="text-lg">Month filter</CardTitle>
                        <CardDescription>Showing {{ filters.monthLabel }} against {{ shift.label }}.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="applyMonthFilter">
                            <div class="grid gap-2">
                                <Label for="attendance-month">Month</Label>
                                <Input id="attendance-month" v-model="monthFilter" type="month" />
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <Button type="submit">Apply month</Button>
                                <Button type="button" variant="outline" @click="monthFilter = filters.month">Reset</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card v-for="stat in summary" :key="stat.label" class="border-border/70">
                    <CardContent class="space-y-3 px-5 py-5">
                        <p class="text-sm font-medium text-muted-foreground">{{ stat.label }}</p>
                        <p class="text-3xl font-semibold tracking-tight">{{ stat.value }}</p>
                        <p class="text-sm text-muted-foreground">{{ stat.description }}</p>
                    </CardContent>
                </Card>
            </section>

            <section class="space-y-4">
                <Heading title="Timesheet Entries" description="Raw punches stay on the left, effective edited values stay on the right." />

                <div v-if="entries.length > 0" class="grid gap-4">
                    <Card v-for="entry in entries" :key="entry.id" class="border-border/70">
                        <CardContent class="space-y-5 px-5 py-5">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-lg font-semibold tracking-tight">{{ entry.workDateLabel }}</p>
                                        <Badge variant="outline" :class="badgeClass(entry.status)">{{ entry.status }}</Badge>
                                        <Badge v-if="entry.hasTimesheetOverrides" variant="outline" class="border-primary/20 bg-primary/5 text-primary">Edited</Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ site.name }} · {{ site.radiusMeters }}m geofence</p>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-3">
                                    <div class="rounded-xl border px-4 py-3 text-sm" :class="metricClass(entry.lateMinutes)">Late {{ entry.lateMinutes }}m</div>
                                    <div class="rounded-xl border px-4 py-3 text-sm" :class="metricClass(entry.undertimeMinutes)">UT {{ entry.undertimeMinutes }}m</div>
                                    <div class="rounded-xl border px-4 py-3 text-sm" :class="metricClass(entry.overtimeMinutes)">OT {{ entry.overtimeMinutes }}m</div>
                                </div>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-2">
                                <div class="rounded-2xl border border-border/70 bg-muted/20 p-4 text-sm">
                                    <p class="font-medium text-muted-foreground">Raw attendance log</p>
                                    <div class="mt-4 grid gap-3">
                                        <div class="flex items-center gap-2"><Clock3 class="size-4 text-muted-foreground" /><span>Time in: {{ entry.rawClockIn ?? 'Not logged' }}</span></div>
                                        <div class="flex items-center gap-2"><TimerReset class="size-4 text-muted-foreground" /><span>Time out: {{ entry.rawClockOut ?? 'Not logged' }}</span></div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-border/70 bg-muted/20 p-4 text-sm">
                                    <p class="font-medium text-muted-foreground">Effective timesheet</p>
                                    <div class="mt-4 grid gap-3">
                                        <div class="flex items-center gap-2"><NotebookPen class="size-4 text-muted-foreground" /><span>Start: {{ entry.effectiveClockIn ?? 'Not set' }}</span></div>
                                        <div class="flex items-center gap-2"><Clock3 class="size-4 text-muted-foreground" /><span>End: {{ entry.effectiveClockOut ?? 'Not set' }}</span></div>
                                    </div>
                                </div>
                            </div>

                            <form class="grid gap-4 rounded-2xl border border-border/70 bg-card/70 p-4" @submit.prevent="submitTimesheet(entry.id)">
                                <AlertError v-if="errorsForEntry(entry.id).length > 0" :errors="errorsForEntry(entry.id)" title="This timesheet update could not be saved." />

                                <div class="grid gap-4 xl:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label :for="`timesheet-start-${entry.id}`">Edited start time</Label>
                                        <Input :id="`timesheet-start-${entry.id}`" v-model="drafts[entry.id].timesheet_start_at" type="datetime-local" />
                                        <InputError :message="rowErrors[entry.id]?.timesheet_start_at" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`timesheet-end-${entry.id}`">Edited end time</Label>
                                        <Input :id="`timesheet-end-${entry.id}`" v-model="drafts[entry.id].timesheet_end_at" type="datetime-local" />
                                        <InputError :message="rowErrors[entry.id]?.timesheet_end_at" />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`timesheet-note-${entry.id}`">Notes</Label>
                                    <textarea
                                        :id="`timesheet-note-${entry.id}`"
                                        v-model="drafts[entry.id].timesheet_notes"
                                        :class="textareaClass"
                                        placeholder="Add context when the effective times differ from the raw log."
                                    />
                                    <InputError :message="rowErrors[entry.id]?.timesheet_notes" />
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <Button type="submit" :disabled="savingEntryId === entry.id">
                                        {{ savingEntryId === entry.id ? 'Saving...' : 'Save timesheet' }}
                                    </Button>
                                    <p v-if="savedEntryId === entry.id" class="text-sm text-emerald-600 dark:text-emerald-400">Saved.</p>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else class="border-dashed border-border/80 bg-muted/20">
                    <CardContent class="px-6 py-10 text-center text-sm text-muted-foreground">
                        No attendance entries were found for {{ filters.monthLabel }}.
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
