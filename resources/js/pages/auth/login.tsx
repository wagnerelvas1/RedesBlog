import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/Button';
import { Checkbox } from '@/components/ui/Checkbox';
import { Input } from '@/components/ui/Input';
import { AuthLayout } from '@/layouts/AuthLayout';
import { Form, Head, Link } from '@inertiajs/react';
import { register } from '@/routes';
import { store as loginStore } from '@/routes/login';

export default function Login() {
    return (
        <AuthLayout title="Log in" description="Welcome back to RedesBlog.">
            <Head title="Log in" />

            <Form {...loginStore.form()} className="space-y-4">
                {({ errors, processing }) => (
                    <>
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
                                autoFocus
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
                                autoComplete="current-password"
                                required
                                invalid={Boolean(errors.password)}
                            />
                        </FormField>

                        <Checkbox
                            id="remember"
                            name="remember"
                            value="1"
                            label="Remember me"
                        />

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing ? 'Logging in…' : 'Log in'}
                        </Button>

                        <p className="text-muted text-center text-sm">
                            New here?{' '}
                            <Link
                                href={register().url}
                                className="text-primary font-semibold hover:underline"
                            >
                                Create an account
                            </Link>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
