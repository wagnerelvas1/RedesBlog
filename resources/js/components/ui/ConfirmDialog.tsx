import { useState } from 'react';
import { Button } from './Button';
import { Input } from './Input';
import { Modal } from './Modal';

export type ConfirmDialogProps = {
    open: boolean;
    title: string;
    description?: string;
    /** When set, the user must type this exact value to enable confirmation. */
    confirmPhrase?: string;
    confirmLabel?: string;
    processing?: boolean;
    onConfirm: (phrase: string) => void;
    onClose: () => void;
};

export function ConfirmDialog({
    open,
    title,
    description,
    confirmPhrase,
    confirmLabel = 'Confirm',
    processing = false,
    onConfirm,
    onClose,
}: ConfirmDialogProps) {
    const [typed, setTyped] = useState('');
    const matches = !confirmPhrase || typed === confirmPhrase;

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={title}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        variant="danger"
                        disabled={!matches || processing}
                        onClick={() => onConfirm(typed)}
                    >
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            {description ? (
                <p className="text-sm text-muted">{description}</p>
            ) : null}
            {confirmPhrase ? (
                <div className="mt-4 space-y-2">
                    <label
                        htmlFor="confirm-phrase"
                        className="block text-sm font-medium text-text"
                    >
                        Type <span className="font-mono">{confirmPhrase}</span>{' '}
                        to confirm
                    </label>
                    <Input
                        id="confirm-phrase"
                        value={typed}
                        autoComplete="off"
                        onChange={(event) => setTyped(event.target.value)}
                    />
                </div>
            ) : null}
        </Modal>
    );
}
