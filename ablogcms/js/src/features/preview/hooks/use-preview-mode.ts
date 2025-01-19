import { useState, useCallback, useRef } from 'react';
import * as api from '../api';
import { reloadIframe } from '../../../utils';
import { rewriteUrl } from '../utils';

export default function usePreviewMode({ initialUrl = '' } = {}) {
  const iframeRef = useRef<HTMLIFrameElement | null>(null);
  const [url, setUrl] = useState(rewriteUrl(initialUrl));
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const setIframeRef = useCallback((iframe: HTMLIFrameElement | null) => {
    iframeRef.current = iframe;
  }, []);

  const changeUrl = useCallback((url: string) => {
    setUrl(rewriteUrl(url));
  }, []);

  const doEnablePreviewMode = useCallback(async (ua: string) => {
    const formData = new FormData();
    formData.append('preview_fake_ua', ua);
    formData.append('preview_token', window.csrfToken);
    await api.enablePreviewMode(formData);
    if (iframeRef.current) {
      await reloadIframe(iframeRef.current);
    }
  }, []);

  const enablePreviewMode = useCallback(
    async (ua: string) => {
      setIsLoading(true);
      try {
        await doEnablePreviewMode(ua);
      } catch (error) {
        console.error(error); // eslint-disable-line no-console
        if (error instanceof Error) {
          setError(new Error(error.message));
        }
      } finally {
        setIsLoading(false);
      }
    },
    [doEnablePreviewMode]
  );

  const disablePreviewMode = useCallback(async () => {
    try {
      await api.disablePreviewMode();
    } catch (error) {
      console.error(error); // eslint-disable-line no-console
      if (error instanceof Error) {
        setError(new Error(error.message));
      }
    }
  }, []);

  return { setIframeRef, isLoading, enablePreviewMode, disablePreviewMode, error, url, changeUrl };
}
