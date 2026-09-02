import { FormField } from '@/components/FormField';
import { ImageUploader } from '@/components/ImageUploader';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Textarea } from '@/components/ui/Textarea';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import type { Community, Post } from '@/types';
import { update as postUpdate } from '@/routes/posts';

export default function EditPost({
    community,
    post,
}: {
    community: Community;
    post: Post;
}) {
    const form = useForm({
        title: post.title,
        body: post.body ?? '',
        images: [] as File[],
        existing_images: post.attachments.map((image) => image.id),
    });

    return (
        <>
            <Head title={`Edit · ${post.title}`} />

            <h1 className="text-text mb-3 text-xl font-bold">Edit post</h1>

            <Card>
                <CardBody>
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.transform((payload) => ({
                                ...payload,
                                _method: 'patch',
                            }));
                            form.post(
                                postUpdate([community.name, post.id]).url,
                                { forceFormData: true },
                            );
                        }}
                    >
                        <FormField
                            id="title"
                            label="Title"
                            error={form.errors.title}
                        >
                            <Input
                                id="title"
                                value={form.data.title}
                                maxLength={300}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                                required
                                invalid={Boolean(form.errors.title)}
                            />
                        </FormField>

                        <FormField
                            id="body"
                            label="Body"
                            error={form.errors.body}
                        >
                            <Textarea
                                id="body"
                                rows={8}
                                value={form.data.body}
                                onChange={(event) =>
                                    form.setData('body', event.target.value)
                                }
                            />
                        </FormField>

                        <ImageUploader
                            files={form.data.images}
                            onFilesChange={(files) =>
                                form.setData('images', files)
                            }
                            existing={post.attachments.map((image) => ({
                                id: image.id,
                                url: image.url,
                                name: image.original_name,
                            }))}
                            keptIds={form.data.existing_images}
                            onKeptIdsChange={(ids) =>
                                form.setData('existing_images', ids)
                            }
                            max={10}
                            error={form.errors.images}
                        />

                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </Button>
                    </form>
                </CardBody>
            </Card>
        </>
    );
}

EditPost.layout = [AppLayout];
