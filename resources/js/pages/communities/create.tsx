import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Textarea } from '@/components/ui/Textarea';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { store as communityStore } from '@/routes/communities';

const NAME_PATTERN = /^[A-Za-z0-9_]{3,21}$/;

export default function CreateCommunity() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        title: '',
        description: '',
        rules: '',
        avatar: null as File | null,
        banner: null as File | null,
    });

    const [touched, setTouched] = useState(false);
    const nameValid = NAME_PATTERN.test(data.name);

    return (
        <>
            <Head title="Create a community" />

            <div className="border-border bg-surface rounded-lg border p-4">
                <h1 className="text-text text-xl font-bold">
                    Create a community
                </h1>
                <p className="text-muted mt-1 text-sm">
                    Pick the name carefully — it can never be changed.
                </p>

                <form
                    className="mt-5 space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(communityStore().url, { forceFormData: true });
                    }}
                >
                    <FormField
                        id="name"
                        label="Community name"
                        error={
                            errors.name ??
                            (touched && !nameValid
                                ? '3 to 21 letters, numbers or underscores.'
                                : undefined)
                        }
                        hint="This becomes the URL: /c/your_name. It is permanent."
                    >
                        <div className="flex items-center gap-1">
                            <span className="text-muted text-sm">c/</span>
                            <Input
                                id="name"
                                value={data.name}
                                onBlur={() => setTouched(true)}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                invalid={Boolean(errors.name)}
                            />
                        </div>
                    </FormField>

                    <FormField
                        id="title"
                        label="Display title"
                        error={errors.title}
                    >
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(event) =>
                                setData('title', event.target.value)
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
                                setData('description', event.target.value)
                            }
                        />
                    </FormField>

                    <FormField
                        id="rules"
                        label="Rules"
                        error={errors.rules}
                        hint="Markdown is supported."
                    >
                        <Textarea
                            id="rules"
                            rows={5}
                            value={data.rules}
                            onChange={(event) =>
                                setData('rules', event.target.value)
                            }
                        />
                    </FormField>

                    <div className="grid gap-4 sm:grid-cols-2">
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
                                        event.target.files?.[0] ?? null,
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
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </FormField>
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing ? 'Creating…' : 'Create community'}
                    </Button>
                </form>
            </div>
        </>
    );
}

CreateCommunity.layout = [AppLayout];
