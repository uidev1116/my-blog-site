import { useMemo } from 'react';

type ReactRef<T> = React.Ref<T> | React.MutableRefObject<T>;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function assignRef<T = any>(ref: ReactRef<T> | undefined, value: T) {
  if (ref == null) return;

  if (typeof ref === 'function') {
    ref(value);
    return;
  }

  try {
    // @ts-expect-error https://github.com/chakra-ui/chakra-ui/blob/fd231f720965b505faf5a0d8153366f8989ec9ce/packages/hooks/src/use-merge-refs.ts
    ref.current = value;
  } catch {
    throw new Error(`Cannot assign value '${value}' to ref '${ref}'`);
  }
}

export default function useMergeRefs<T>(...refs: (ReactRef<T> | undefined)[]) {
  return useMemo(() => {
    if (refs.every((ref) => ref == null)) {
      return null;
    }
    return (node: T) => {
      refs.forEach((ref) => {
        if (ref) {
          assignRef(ref, node);
        }
      });
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, refs);
}
