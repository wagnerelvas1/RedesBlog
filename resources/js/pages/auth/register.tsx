import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { AuthLayout } from '@/layouts/AuthLayout';
import { Form, Head, Link } from '@inertiajs/react';
import { login } from '@/routes';
import { store as registerStore } from '@/routes/register';

export default function Register() {
    return (
        <AuthLayout
            title="Create your account"
            description="Join communities, post, and take part in the conversation."
        >
            <Head title="Register" />

            <Form {...registerStore.form()} className="space-y-4">
                {({ errors, processing }) => (
                    <>
                        <FormField
                            id="name"
                            label="Display name"
                            error={errors.name}
                        >
                            <Input
                                id="name"
                                name="name"
                                autoComplete="name"
                                required
                                invalid={Boolean(errors.name)}
                            />
                        </FormField>

                        <FormField
                            id="username"
                            label="Username"
                            error={errors.username}
                            hint="Letters, numbers and underscores. This is your /u/ handle."
                        >
                            <Input
                                id="username"
                                name="username"
                                autoComplete="username"
                                required
                                invalid={Boolean(errors.username)}
                            />
                        </FormField>

                        <FormField
                            id="email"
                            label="Email"
                            error={errors.email}
                        >
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                required
                                invalid={Boolean(errors.email)}
                            />
                        </FormField>

                        <FormField
                            id="password"
                            label="Password"
                            error={errors.password}
                        >
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                required
                                invalid={Boolean(errors.password)}
                            />
                        </FormField>

                        <FormField
                            id="password_confirmation"
                            label="Confirm password"
                            error={errors.password_confirmation}
                        >
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                required
                            />
                        </FormField>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing ? 'Creating account…' : 'Sign up'}
                        </Button>

                        <p className="text-muted text-center text-sm">
                            Already have an account?{' '}
                            <Link
                                href={login().url}
                                className="text-primary font-semibold hover:underline"
                            >
                                Log in
                            </Link>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
