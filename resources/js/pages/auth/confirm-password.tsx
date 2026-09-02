import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { AuthLayout } from '@/layouts/AuthLayout';
import { Form, Head } from '@inertiajs/react';
import { store as confirmStore } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <AuthLayout
            title="Confirm your password"
            description="This is a sensitive action. Please confirm your password to continue."
        >
            <Head title="Confirm password" />

            <Form {...confirmStore.form()} className="space-y-4">
                {({ errors, processing }) => (
                    <>
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
                                autoFocus
                                invalid={Boolean(errors.password)}
                            />
                        </FormField>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing ? 'Confirming…' : 'Confirm'}
                        </Button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
