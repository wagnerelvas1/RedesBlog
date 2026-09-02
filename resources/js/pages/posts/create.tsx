import { FormField } from '@/components/FormField';
import { ImageUploader } from '@/components/ImageUploader';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Textarea } from '@/components/ui/Textarea';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import type { Community } from '@/types';
import { store as postStore } from '@/routes/posts';

export default function CreatePost({ community }: { community: Community }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        body: '',
        images: [] as File[],
    });

    return (
        <>
            <Head title={`Post to c/${community.name}`} />

            <h1 className="text-text mb-3 text-xl font-bold">
                Create a post in c/{community.name}
            </h1>

            <Card>
                <CardBody>
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            post(postStore(community.name).url, {
                                forceFormData: true,
                            });
                        }}
                    >
                        <FormField
                            id="title"
                            label="Title"
                            error={errors.title}
                            hint={`${data.title.length}/300`}
                        >
                            <Input
                                id="title"
                                value={data.title}
                                maxLength={300}
                                onChange={(event) =>
                                    setData('title', event.target.value)
                                }
                                required
                                autoFocus
                                invalid={Boolean(errors.title)}
                            />
                        </FormField>

                        <FormField
                            id="body"
                            label="Body"
                            error={errors.body}
                            hint="Optional. Markdown is supported."
                        >
                            <Textarea
                                id="body"
                                rows={8}
                                value={data.body}
                                onChange={(event) =>
                                    setData('body', event.target.value)
                                }
                            />
                        </FormField>

                        <ImageUploader
                            files={data.images}
                            onFilesChange={(files) => setData('images', files)}
                            max={10}
                            error={errors.images}
                        />

                        <Button type="submit" disabled={processing}>
                            {processing ? 'Publishing…' : 'Post'}
                        </Button>
                    </form>
                </CardBody>
            </Card>
        </>
    );
}

CreatePost.layout = [AppLayout];
