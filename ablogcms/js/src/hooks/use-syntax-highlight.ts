import { useEffect, useState } from 'react';
import hljs, { HighlightOptions, HighlightResult } from 'highlight.js';

// eslint-disable-next-line @typescript-eslint/no-empty-object-type
export interface UseSyntaxHighlightOptions extends HighlightOptions {}

export default function useSyntaxHighlight(code: string, options: UseSyntaxHighlightOptions) {
  const [result, setResult] = useState<Omit<HighlightResult, '_illegalBy' | '_emitter' | '_top'>>({
    relevance: 0,
    value: '',
    illegal: false,
  });

  useEffect(() => {
    const newResult = hljs.highlight(code, options);
    // 結果が変更されていない場合は状態を更新しない
    // これをしないと、無限ループになる
    if (result === null || newResult.value !== result.value) {
      setResult(newResult);
    }
  }, [options, code, result]);

  return result;
}
