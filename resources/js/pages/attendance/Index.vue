<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, Clock3, MapPin, NotebookPen, ShieldCheck } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import AttendanceController from '@/actions/App/Http/Controllers/AttendanceController';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as attendanceIndex } from '@/routes/attendance';
import type { AttendanceIndexPageProps, BreadcrumbItem } from '@/types';

const props = defineProps<AttendanceIndexPageProps>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Attendance', href: attendanceIndex() }];
const videoElement = ref<HTMLVideoElement | null>(null);
const cameraStream = ref<MediaStream | null>(null);
const capturedPhotoUrl = ref<string | null>(null);
const locationError = ref<string | null>(null);
const cameraError = ref<string | null>(null);
const clientErrors = ref<string[]>([]);
const loadingLocation = ref(false);
const loadingCamera = ref(false);

const punchForm = useForm<{ captured_at: string; latitude: number | null; longitude: number | null; photo: File | null }>({
    captured_at: '',
    latitude: null,
    longitude: null,
    photo: null,
});

const punchErrors = computed(() => [...clientErrors.value, ...Object.values(punchForm.errors)]);
const currentDistance = computed<number | null>(() => {
    if (punchForm.latitude === null || punchForm.longitude === null) {
        return null;
    }

    return distanceMeters(punchForm.latitude, punchForm.longitude, props.site.latitude, props.site.longitude);
});
const insideFence = computed(() => currentDistance.value !== null && currentDistance.value <= props.site.radiusMeters);
const actionLabel = computed(() => props.clockState.canClockIn ? 'Clock in' : props.clockState.canClockOut ? 'Clock out' : 'Completed');

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

const requestLocation = (): void => {
    clientErrors.value = [];
    locationError.value = null;

    if (!('geolocation' in navigator)) {
        locationError.value = 'Geolocation is not available in this browser.';

        return;
    }

    loadingLocation.value = true;

    navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
            punchForm.latitude = coords.latitude;
            punchForm.longitude = coords.longitude;
            loadingLocation.value = false;
        },
        (error) => {
            locationError.value = error.message || 'Unable to read the current location.';
            loadingLocation.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
    );
};

const startCamera = async (): Promise<void> => {
    cameraError.value = null;

    if (!navigator.mediaDevices?.getUserMedia) {
        cameraError.value = 'Camera capture is not available in this browser.';

        return;
    }

    loadingCamera.value = true;

    try {
        stopCamera();
        cameraStream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });

        if (videoElement.value) {
            videoElement.value.srcObject = cameraStream.value;
        }
    } catch (error) {
        cameraError.value = error instanceof Error ? error.message : 'Unable to start the camera.';
    } finally {
        loadingCamera.value = false;
    }
};

const capturePhoto = async (): Promise<void> => {
    if (!videoElement.value || videoElement.value.videoWidth === 0) {
        cameraError.value = 'Start the camera before capturing a face photo.';

        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = videoElement.value.videoWidth;
    canvas.height = videoElement.value.videoHeight;
    canvas.getContext('2d')?.drawImage(videoElement.value, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));

    if (!blob) {
        cameraError.value = 'Unable to capture a face photo.';

        return;
    }

    if (capturedPhotoUrl.value) {
        URL.revokeObjectURL(capturedPhotoUrl.value);
    }

    const photo = new File([blob], `attendance-${Date.now()}.jpg`, { type: 'image/jpeg' });
    punchForm.photo = photo;
    capturedPhotoUrl.value = URL.createObjectURL(photo);
    stopCamera();
};

const clearPhoto = (): void => {
    if (capturedPhotoUrl.value) {
        URL.revokeObjectURL(capturedPhotoUrl.value);
    }

    capturedPhotoUrl.value = null;
    punchForm.photo = null;
};

const submitPunch = (): void => {
    clientErrors.value = [];

    if (punchForm.latitude === null || punchForm.longitude === null) {
        clientErrors.value.push('Current location is required.');
    }

    if (!insideFence.value) {
        clientErrors.value.push(`Move within ${props.site.radiusMeters} meters of ${props.site.name}.`);
    }

    if (punchForm.photo === null) {
        clientErrors.value.push('A face photo is required.');
    }

    if (clientErrors.value.length > 0 || (!props.clockState.canClockIn && !props.clockState.canClockOut)) {
        return;
    }

    punchForm.captured_at = new Date().toISOString();
    punchForm.post(props.clockState.canClockIn ? AttendanceController.clockIn.url() : AttendanceController.clockOut.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearPhoto();
            stopCamera();
        },
    });
};

const stopCamera = (): void => {
    cameraStream.value?.getTracks().forEach((track) => track.stop());
    cameraStream.value = null;

    if (videoElement.value) {
        videoElement.value.srcObject = null;
    }
};

onBeforeUnmount(() => {
    stopCamera();
    clearPhoto();
});

function distanceMeters(latitude: number, longitude: number, siteLatitude: number, siteLongitude: number): number {
    const earthRadius = 6371000;
    const latitudeDelta = ((siteLatitude - latitude) * Math.PI) / 180;
    const longitudeDelta = ((siteLongitude - longitude) * Math.PI) / 180;
    const a = Math.sin(latitudeDelta / 2) ** 2
        + Math.cos((latitude * Math.PI) / 180) * Math.cos((siteLatitude * Math.PI) / 180) * Math.sin(longitudeDelta / 2) ** 2;

    return earthRadius * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(Math.max(0, 1 - a))));
}
</script>

<template>
    <Head title="Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <section class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <Card class="border-border/70 bg-gradient-to-br from-background via-background to-muted/45">
                    <CardHeader class="gap-4 sm:flex sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <Badge variant="outline" class="border-primary/20 bg-primary/5 text-primary">Self-service attendance</Badge>
                            <CardTitle class="text-2xl tracking-tight">Attendance</CardTitle>
                            <CardDescription class="max-w-2xl text-sm leading-6">
                                Real clock in/out with geofence, face capture, and simple fixed-shift tracking.
                            </CardDescription>
                        </div>

                        <div class="grid gap-2 rounded-xl border border-border/70 bg-background/80 p-4 text-sm">
                            <div class="flex items-center gap-2">
                                <MapPin class="size-4 text-muted-foreground" />
                                <span>{{ site.name }} · {{ site.radiusMeters }}m</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Clock3 class="size-4 text-muted-foreground" />
                                <span>{{ shift.label }}</span>
                            </div>
                            <Button variant="outline" as-child>
                                <Link :href="AttendanceController.timesheets()">
                                    <NotebookPen />
                                    Timesheets
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                </Card>

                <Card class="border-border/70">
                    <CardHeader>
                        <CardTitle class="text-lg">{{ clockState.title }}</CardTitle>
                        <CardDescription>{{ clockState.description }}</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                            <p class="text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">Late</p>
                            <p class="mt-2 text-2xl font-semibold">{{ today?.lateMinutes ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">minutes</p>
                        </div>
                        <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                            <p class="text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">Undertime</p>
                            <p class="mt-2 text-2xl font-semibold">{{ today?.undertimeMinutes ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">minutes</p>
                        </div>
                        <div class="rounded-xl border border-border/70 bg-card/80 p-4">
                            <p class="text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">Overtime</p>
                            <p class="mt-2 text-2xl font-semibold">{{ today?.overtimeMinutes ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">minutes</p>
                        </div>
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

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <Card class="border-border/70">
                    <CardHeader>
                        <CardTitle class="text-xl">Punch now</CardTitle>
                        <CardDescription>Location and face photo are required before submission.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <AlertError v-if="punchErrors.length > 0" :errors="punchErrors" title="Attendance could not be submitted." />

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="space-y-3 rounded-2xl border border-border/70 bg-muted/20 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-medium">Location</p>
                                        <p class="text-sm text-muted-foreground">Check your live distance from the site.</p>
                                    </div>
                                    <Button type="button" variant="outline" :disabled="loadingLocation" @click="requestLocation">
                                        <MapPin />
                                        {{ loadingLocation ? 'Checking...' : 'Use current location' }}
                                    </Button>
                                </div>
                                <div class="rounded-xl border px-4 py-3 text-sm" :class="insideFence ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-border/70 bg-card/80 text-muted-foreground'">
                                    <p class="font-medium">
                                        {{ currentDistance === null ? 'Current location not captured yet' : `${Math.round(currentDistance)} meters from ${site.name}` }}
                                    </p>
                                    <p class="mt-1">
                                        {{ currentDistance === null ? 'Capture your location first.' : insideFence ? 'Inside the allowed radius.' : `Outside the ${site.radiusMeters}-meter geofence.` }}
                                    </p>
                                </div>
                                <p v-if="locationError" class="text-sm text-rose-600 dark:text-rose-400">{{ locationError }}</p>
                            </div>

                            <div class="space-y-3 rounded-2xl border border-border/70 bg-muted/20 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-medium">Face capture</p>
                                        <p class="text-sm text-muted-foreground">A fresh photo is stored with each punch.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <Button type="button" variant="outline" :disabled="loadingCamera" @click="startCamera">
                                            <Camera />
                                            {{ loadingCamera ? 'Starting...' : 'Enable camera' }}
                                        </Button>
                                        <Button type="button" :disabled="cameraStream === null" @click="capturePhoto">Capture</Button>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-border/70 bg-black/90">
                                    <video ref="videoElement" autoplay muted playsinline class="aspect-[4/3] w-full object-cover" />
                                </div>
                                <div v-if="capturedPhotoUrl" class="overflow-hidden rounded-2xl border border-border/70 bg-card">
                                    <img :src="capturedPhotoUrl" alt="Captured attendance selfie" class="aspect-[4/3] w-full object-cover" />
                                </div>
                                <Button v-if="capturedPhotoUrl" type="button" variant="outline" @click="clearPhoto">Retake photo</Button>
                                <p v-if="cameraError" class="text-sm text-rose-600 dark:text-rose-400">{{ cameraError }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/80 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-2">
                                <Badge variant="outline" :class="badgeClass(clockState.status === 'complete' ? 'Complete' : clockState.status === 'ready-to-clock-out' ? 'Open' : 'Draft')">
                                    {{ clockState.title }}
                                </Badge>
                                <p class="text-sm text-muted-foreground">{{ clockState.description }}</p>
                            </div>
                            <Button type="button" size="lg" :disabled="punchForm.processing || (!clockState.canClockIn && !clockState.canClockOut)" @click="submitPunch">
                                <ShieldCheck />
                                {{ punchForm.processing ? 'Submitting...' : actionLabel }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card class="border-border/70">
                        <CardHeader>
                            <CardTitle class="text-xl">Today&apos;s record</CardTitle>
                            <CardDescription>Raw punches stay visible even after a timesheet edit.</CardDescription>
                        </CardHeader>
                        <CardContent v-if="today" class="space-y-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" :class="badgeClass(today.status)">{{ today.status }}</Badge>
                                <Badge v-if="today.hasTimesheetOverrides" variant="outline" class="border-primary/20 bg-primary/5 text-primary">Edited</Badge>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-border/70 bg-muted/20 p-4 text-sm">
                                    <p class="font-medium text-muted-foreground">Raw</p>
                                    <div class="mt-3 grid gap-2">
                                        <p><span class="font-medium text-foreground">In:</span> {{ today.rawClockIn ?? 'Not logged' }}</p>
                                        <p><span class="font-medium text-foreground">Out:</span> {{ today.rawClockOut ?? 'Not logged' }}</p>
                                        <p><span class="font-medium text-foreground">In location:</span> {{ today.clockInDistance ?? 'Pending' }}</p>
                                        <p><span class="font-medium text-foreground">Out location:</span> {{ today.clockOutDistance ?? 'Pending' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-border/70 bg-muted/20 p-4 text-sm">
                                    <p class="font-medium text-muted-foreground">Effective</p>
                                    <div class="mt-3 grid gap-2">
                                        <p><span class="font-medium text-foreground">Start:</span> {{ today.effectiveClockIn ?? 'Not set' }}</p>
                                        <p><span class="font-medium text-foreground">End:</span> {{ today.effectiveClockOut ?? 'Not set' }}</p>
                                        <p><span class="font-medium text-foreground">Face capture:</span> {{ today.clockInPhotoCaptured || today.clockOutPhotoCaptured ? 'Saved' : 'Pending' }}</p>
                                        <p><span class="font-medium text-foreground">Date:</span> {{ today.workDateLabel ?? 'Today' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border px-4 py-3 text-sm" :class="metricClass(today.lateMinutes)">Late {{ today.lateMinutes }}m</div>
                                <div class="rounded-xl border px-4 py-3 text-sm" :class="metricClass(today.undertimeMinutes)">UT {{ today.undertimeMinutes }}m</div>
                                <div class="rounded-xl border px-4 py-3 text-sm" :class="metricClass(today.overtimeMinutes)">OT {{ today.overtimeMinutes }}m</div>
                            </div>

                            <div v-if="today.timesheetNotes" class="rounded-xl border border-border/70 bg-card p-4 text-sm text-muted-foreground">
                                <p class="font-medium text-foreground">Timesheet note</p>
                                <p class="mt-2 leading-6">{{ today.timesheetNotes }}</p>
                            </div>
                        </CardContent>
                        <CardContent v-else class="rounded-2xl border border-dashed border-border/80 bg-muted/20 p-6 text-sm text-muted-foreground">
                            No attendance has been recorded yet for today.
                        </CardContent>
                    </Card>

                    <Card class="border-border/70">
                        <CardHeader>
                            <CardTitle class="text-xl">Rules</CardTitle>
                            <CardDescription>The first pass is intentionally narrow.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-3 text-sm">
                            <div class="flex items-start gap-3 rounded-xl border border-border/70 bg-muted/20 p-4">
                                <ShieldCheck class="mt-0.5 size-4 text-muted-foreground" />
                                <p>One company site and a hard {{ site.radiusMeters }}-meter geofence.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl border border-border/70 bg-muted/20 p-4">
                                <Clock3 class="mt-0.5 size-4 text-muted-foreground" />
                                <p>One time-in and one time-out per day using the fixed {{ shift.label }} shift.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <section class="space-y-4">
                <Heading title="Recent Attendance History" description="Your latest attendance days with simple anomaly totals." />

                <div v-if="recentAttendances.length > 0" class="grid gap-4">
                    <Card v-for="entry in recentAttendances" :key="entry.id" class="border-border/70">
                        <CardContent class="grid gap-4 px-5 py-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-lg font-semibold tracking-tight">{{ entry.workDateLabel }}</p>
                                    <Badge variant="outline" :class="badgeClass(entry.status)">{{ entry.status }}</Badge>
                                    <Badge v-if="entry.hasTimesheetOverrides" variant="outline" class="border-primary/20 bg-primary/5 text-primary">Edited</Badge>
                                </div>
                                <div class="grid gap-3 text-sm text-muted-foreground sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="flex items-center gap-2"><Clock3 class="size-4" /><span>In {{ entry.rawClockIn ?? 'Pending' }}</span></div>
                                    <div class="flex items-center gap-2"><TimerReset class="size-4" /><span>Out {{ entry.rawClockOut ?? 'Pending' }}</span></div>
                                    <div class="flex items-center gap-2"><NotebookPen class="size-4" /><span>Late {{ entry.lateMinutes }}m</span></div>
                                    <div class="flex items-center gap-2"><ShieldCheck class="size-4" /><span>OT {{ entry.overtimeMinutes }}m · UT {{ entry.undertimeMinutes }}m</span></div>
                                </div>
                            </div>

                            <Button variant="outline" as-child>
                                <Link :href="AttendanceController.timesheets()">Review timesheets</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else class="border-dashed border-border/80 bg-muted/20">
                    <CardContent class="px-6 py-10 text-center text-sm text-muted-foreground">
                        Recent attendance days will appear here after your first completed punch.
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
