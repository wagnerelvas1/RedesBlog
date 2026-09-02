import { AvatarUploader } from '@/components/AvatarUploader';
import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Input } from '@/components/ui/Input';
import { Textarea } from '@/components/ui/Textarea';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    destroy as profileDestroy,
    update as profileUpdate,
} from '@/routes/profile';

type Props = {
    profile: {
        name: string;
        username: string;
        email: string;
        bio: string | null;
        avatar_url: string | null;
    };
};

export default function ProfileSettings({ profile }: Props) {
    const [confirming, setConfirming] = useState(false);

    const form = useForm({
        name: profile.name,
        username: profile.username,
        bio: profile.bio ?? '',
        avatar: null as File | null,
        remove_avatar: false as boolean,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((payload) => ({ ...payload, _method: 'patch' }));
        form.post(profileUpdate().url, { forceFormData: true });
    }

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="text-text mb-3 text-xl font-bold">
                Profile settings
            </h1>

            <Card>
                <CardBody>
                    <form className="space-y-5" onSubmit={submit}>
                        <AvatarUploader
                            currentUrl={profile.avatar_url}
                            name={profile.name}
                            file={form.data.avatar}
                            removed={form.data.remove_avatar}
                            onFileChange={(file) => {
                                form.setData('avatar', file);
                                if (file) {
                                    form.setData('remove_avatar', false);
                                }
                            }}
                            onRemove={() => form.setData('remove_avatar', true)}
                        />

                        <FormField
                            id="name"
                            label="Display name"
                            error={form.errors.name}
                        >
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                required
                                invalid={Boolean(form.errors.name)}
                            />
                        </FormField>

                        <FormField
                            id="username"
                            label="Username"
                            error={form.errors.username}
                            hint="Your public handle at /u/username."
                        >
                            <Input
                                id="username"
                                value={form.data.username}
                                onChange={(event) =>
                                    form.setData('username', event.target.value)
                                }
                                required
                                invalid={Boolean(form.errors.username)}
                            />
                        </FormField>

                        <FormField
                            id="bio"
                            label="Bio"
                            error={form.errors.bio}
                            hint={`${form.data.bio.length}/500`}
                        >
                            <Textarea
                                id="bio"
                                rows={4}
                                maxLength={500}
                                value={form.data.bio}
                                onChange={(event) =>
                                    form.setData('bio', event.target.value)
                                }
                            />
                        </FormField>

                        <div className="text-muted text-sm">
                            Email:{' '}
                            <span className="text-text">{profile.email}</span>
                        </div>

                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save profile'}
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="mt-4 border-red-500/40">
                <CardHeader>
                    <h2 className="text-sm font-bold text-red-500">
                        Delete account
                    </h2>
                </CardHeader>
                <CardBody className="space-y-3">
                    <p className="text-muted text-sm">
                        Permanently deletes your account. You must confirm your
                        password, and you cannot delete while you still own a
                        community.
                    </p>
                    <Button
                        variant="danger"
                        onClick={() => setConfirming(true)}
                    >
                        Delete my account
                    </Button>
                </CardBody>
            </Card>

            <ConfirmDialog
                open={confirming}
                title="Delete your account?"
                description="This cannot be undone. You will be asked to confirm your password."
                confirmPhrase={profile.username}
                confirmLabel="Delete account"
                onClose={() => setConfirming(false)}
                onConfirm={() => router.delete(profileDestroy().url)}
            />
        </>
    );
}

ProfileSettings.layout = [AppLayout];
