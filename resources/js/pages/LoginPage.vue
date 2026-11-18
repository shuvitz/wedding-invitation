<template>
    <div class="min-h-screen flex items-center justify-center bg-background">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Sign In</CardTitle>
                <CardDescription>Welcome back! Enter your credentials.</CardDescription>
            </CardHeader>

            <CardContent>
                <!-- Error alert -->
                <div v-if="errorMessage" class="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                    {{ errorMessage }}
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <!-- Email -->
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="you@example.com" required />
                        <p v-if="auth.errors?.email" class="text-xs text-destructive">{{ auth.errors.email[0] }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input id="password" v-model="form.password" type="password" placeholder="••••••••" required />
                        <p v-if="auth.errors?.password" class="text-xs text-destructive">{{ auth.errors.password[0] }}</p>
                    </div>

                    <!-- Submit -->
                    <Button type="submit" class="w-full" :disabled="auth.isLoading">
                        <span v-if="auth.isLoading">Please wait…</span>
                        <span v-else>Sign In</span>
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const router = useRouter();
const auth = useAuthStore();

const errorMessage = ref('');

const form = reactive({
    email: '',
    password: '',
});

async function handleSubmit() {
    errorMessage.value = '';

    try {
        await auth.login({
            email: form.email,
            password: form.password,
        });

        router.push({ name: 'dashboard' });
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ?? 'Something went wrong. Please try again.';
    }
}
</script>
