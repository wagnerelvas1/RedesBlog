import { Input, type InputProps } from '@/components/ui/Input';
import Inputmask from 'inputmask';
import { useEffect, useRef } from 'react';

/**
 * Options pinned by the project spec: the mask is always visible on hover and
 * on focus. Spread these into every Inputmask instance.
 */
export const MASK_DEFAULTS = {
    showMaskOnHover: true,
    showMaskOnFocus: true,
} as const;

export type MaskedInputProps = InputProps & {
    mask: string;
    maskOptions?: Inputmask.Options;
};

/**
 * Every masked field in the application must go through this component so the
 * pinned options above stay consistent.
 */
export function MaskedInput({ mask, maskOptions, ...props }: MaskedInputProps) {
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const element = inputRef.current;

        if (!element) {
            return;
        }

        const instance = Inputmask({
            mask,
            ...MASK_DEFAULTS,
            ...maskOptions,
        });

        instance.mask(element);

        return () => {
            Inputmask.remove(element);
        };
    }, [mask, maskOptions]);

    return <Input ref={inputRef} {...props} />;
}
