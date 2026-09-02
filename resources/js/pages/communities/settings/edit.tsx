import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Input } from '@/components/ui/Input';
import { Tabs } from '@/components/ui/Tabs';
import { Textarea } from '@/components/ui/Textarea';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { Community } from '@/types';
import { destroy as communityDestroy } from '@/routes/communities';
import { update as settingsUpdate } from '@/routes/communities/settings';
import { index as membersIndex } from '@/routes/communities/members';

type Tab = 'general' | 'rules' | 'danger';

const TABS = [
    { value: 'general' as const, label: 'General' },
    { value: 'rules' as const, label: 'Rules' },
    { value: 'danger' as const, label: 'Danger zone' },
];

export default function CommunitySettings({
    community,
}: {
    community: Community;
}) {
    const [tab, setTab] = useState<Tab>('general');
    const [confirming, setConfirming] = useState(false);

    const { data, setData, processing, errors, transform, post } = useForm({
        title: community.title,
        description: community.description ?? '',
        rules: community.rules ?? '',
        avatar: null as File | null,
        banner: null as File | null,
        remove_avatar: false as boolean,
        remove_banner: false as boolean,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        // Method spoofing so the multipart body reaches a PATCH route.
        transform((payload) => ({ ...payload, _method: 'patch' }));

        post(settingsUpdate(community.name).url, { forceFormData: true });
    }

    return (
        <>
            <Head title={`Settings · c/${community.name}`} />

            <div className="mb-4">
                <h1 className="text-text text-xl font-bold">
                    c/{community.name} settings
                </h1>
                <p className="text-muted text-sm">
                    The community name is permanent and cannot be edited.
                </p>
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Tabs items={TABS} value={tab} onChange={setTab} />
                <Link
                    href={membersIndex(community.name).url}
                    className="text-primary ml-auto text-sm font-semibold hover:underline"
                >
                    Manage members →
                </Link>
            </div>

            {tab === 'danger' ? (
                <Card>
                    <CardHeader>
                        <h2 className="text-sm font-bold text-red-500">
                            Delete this community
                        </h2>
                    </CardHeader>
                    <CardBody className="space-y-3">
                        <p className="text-muted text-sm">
                            This removes the community from the site. You will
                            be asked to confirm your password and to type the
                            community name.
                        </p>
                        <Button
                            variant="danger"
                            onClick={() => setConfirming(true)}
                        >
                            Delete c/{community.name}
                        </Button>
                    </CardBody>
                </Card>
            ) : (
                <form onSubmit={submit}>
                    <Card>
                        <CardBody className="space-y-4">
                            {tab === 'general' ? (
                                <>
                                    <FormField
                                        id="title"
                                        label="Display title"
                                        error={errors.title}
                                    >
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(event) =>
                                                setData(
                                                    'title',
                                                    event.target.value,
                                                )
                                            }
                                            required
                                            invalid={Boolean(errors.title)}
                                        />
                                    </FormField>

                                    <FormField
                                        id="description"
                                        label="Description"
                                        error={errors.description}
                                        hint={`${data.description.length}/500`}
                                    >
                                        <Textarea
                                            id="description"
                                            rows={3}
                                            maxLength={500}
                                            value={data.description}
                                            onChange={(event) =>
                                                setData(
                                                    'description',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </FormField>

                                    <FormField
                                        id="avatar"
                                        label="Avatar"
                                        error={errors.avatar}
                                    >
                                        <Input
                                            id="avatar"
                                            type="file"
                                            accept="image/*"
                                            onChange={(event) =>
                                                setData(
                                                    'avatar',
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />
                                    </FormField>

                                    <FormField
                                        id="banner"
                                        label="Banner"
                                        error={errors.banner}
                                    >
                                        <Input
                                            id="banner"
                                            type="file"
                                            accept="image/*"
                                            onChange={(event) =>
                                                setData(
                                                    'banner',
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />
                                    </FormField>
                                </>
                            ) : (
                                <FormField
                                    id="rules"
                                    label="Rules"
                                    error={errors.rules}
                                    hint="Markdown is supported."
                                >
                                    <Textarea
                                        id="rules"
                                        rows={12}
                                        value={data.rules}
                                        onChange={(event) =>
                                            setData('rules', event.target.value)
                                        }
                                    />
                                </FormField>
                            )}

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : 'Save changes'}
                            </Button>
                        </CardBody>
                    </Card>
                </form>
            )}

            <ConfirmDialog
                open={confirming}
                title={`Delete c/${community.name}?`}
                description="This cannot be undone from the interface."
                confirmPhrase={community.name}
                confirmLabel="Delete community"
                onClose={() => setConfirming(false)}
                onConfirm={(phrase) =>
                    router.delete(communityDestroy(community.name).url, {
                        data: { confirm_name: phrase },
                    })
                }
            />
        </>
    );
}

CommunitySettings.layout = [AppLayout];
