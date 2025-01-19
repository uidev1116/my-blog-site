import { useState, useCallback, useMemo } from 'react';
import type { DeviceChangeEvent } from 'react-device-mode';
import { createPortal } from 'react-dom';
import DeviceMode from '../../../../components/device-mode/device-mode';
import Splash from '../../../../components/splash/splash';
import useCapture from '../../hooks/use-capture';
import usePreviewMode from '../../hooks/use-preview-mode';
import ConfirmModal from '../confirm-modal/confirm-modal';
import PreviewShareModal from '../preview-share-modal/preview-share-modal';
import useEffectOnce from '../../../../hooks/use-effect-once';
import useWindowSize from '../../../../hooks/use-window-size';
import PreviewActions from '../preview-actions/preview-actions';

interface InlinePreviewProps {
  src: string;
  hasShareBtn?: boolean;
  defaultDevice?: string;
  hasHistoryDevice?: boolean;
  historyDeviceKey?: string;
  enableNaked?: boolean;
}

const InlinePreview = ({
  src,
  hasShareBtn = false,
  defaultDevice = '',
  hasHistoryDevice = false,
  historyDeviceKey = 'acms-history-device',
  enableNaked = false,
}: InlinePreviewProps) => {
  const {
    url,
    isLoading,
    enablePreviewMode,
    disablePreviewMode,
    changeUrl,
    setIframeRef: setPreviewIframeRef,
  } = usePreviewMode({
    initialUrl: src,
  });
  const { capture, isCapturing, setIframeRef: setCaputureIframeRef } = useCapture();

  const [isShareModalOpen, setIsShareModalOpen] = useState(false);

  const [windowWidth] = useWindowSize();
  const isNaked = useMemo(() => enableNaked && windowWidth < 768, [windowWidth, enableNaked]);
  // シェアボタンがない場合は共有プレビュー表示とみなしてwindowサイズが小さい場合用のメッセージモーダルを開く
  const [isMessageModalOpened, setIsMessageModalOpened] = useState(!hasShareBtn && isNaked);

  const handleDeviceInit = useCallback(
    async (event: DeviceChangeEvent) => {
      await enablePreviewMode(event.device.ua);
    },
    [enablePreviewMode]
  );

  const handleDeviceUpdated = useCallback(
    async (event: DeviceChangeEvent) => {
      await enablePreviewMode(event.device.ua);
    },
    [enablePreviewMode]
  );

  const handleShare = useCallback(() => {
    setIsShareModalOpen(true);
  }, []);

  const handleCloseShareModal = useCallback(() => {
    setIsShareModalOpen(false);
  }, []);

  const handleCloseMessageModal = useCallback(() => {
    setIsMessageModalOpened(false);
  }, []);

  const handleCapture = useCallback(async () => {
    await capture();
  }, [capture]);

  const handleMessage = useCallback(
    (e: MessageEvent) => {
      if (e.data.task === 'preview') {
        changeUrl(e.data.url);
      }
    },
    [changeUrl]
  );

  useEffectOnce(() => () => {
    (async () => {
      await disablePreviewMode();
    })();
  });

  return (
    <>
      <DeviceMode
        isNaked={isNaked}
        src={url}
        headerRight={<PreviewActions onCapture={handleCapture} onShare={hasShareBtn ? handleShare : undefined} />}
        devices={ACMS.Config.previewDevices}
        defaultDevice={defaultDevice}
        hasHistoryDevice={hasHistoryDevice}
        historyDeviceKey={historyDeviceKey}
        getIframe={(iframe) => {
          setPreviewIframeRef(iframe);
          setCaputureIframeRef(iframe);
        }}
        onDeviceInit={handleDeviceInit}
        onDeviceUpdated={handleDeviceUpdated}
        onMessage={handleMessage}
        hasCloseBtn={false}
        isLoading={isLoading}
      />
      <ConfirmModal isOpen={isMessageModalOpened} onClose={handleCloseMessageModal} container={document.body} />
      <PreviewShareModal
        isOpen={isShareModalOpen}
        onClose={handleCloseShareModal}
        container={document.body}
        url={url}
      />
      {isCapturing && createPortal(<Splash message={ACMS.i18n('preview.capturing')} />, document.body)}
    </>
  );
};

export default InlinePreview;
