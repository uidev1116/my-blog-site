import { useCallback } from 'react';
import Notify from '../../../../components/notify/notify';
import Modal from '../../../../components/modal/modal';
import Spinner from '../../../../components/spinner/spinner';
import useSharePreview from '../../hooks/use-share-preview';
import useClipboard from '../../../../hooks/use-clipboard';

interface PreviewShareModalProps {
  isOpen: boolean;
  onClose: () => void;
  container: HTMLElement;
  url: string;
}

const PreviewShareModal = ({ isOpen, onClose, container, url }: PreviewShareModalProps) => {
  const { status, createShareUrl, shareUrl, setInputRef, standby } = useSharePreview();
  const { copy, copied, reset } = useClipboard();

  const handleCopy = useCallback(() => {
    copy(shareUrl);
  }, [copy, shareUrl]);
  const handleClick = useCallback(async () => {
    await createShareUrl(url);
  }, [createShareUrl, url]);

  const handleClose = useCallback(() => {
    onClose();
    standby();
  }, [onClose, standby]);

  return (
    <>
      <Modal
        isOpen={isOpen}
        onClose={handleClose}
        size="small"
        dialogStyle={{ marginTop: '100px' }}
        aria-labelledby="acms-admin-preview-share-modal-title"
        aria-describedby="acms-admin-preview-share-modal-description"
        container={container}
      >
        <Modal.Header>{ACMS.i18n('preview.share')}</Modal.Header>
        <Modal.Body>
          <div className="acms-admin-padding-small">
            {status === 'standby' && (
              <p>
                <button
                  type="button"
                  className="acms-admin-btn acms-admin-btn-large acms-admin-btn-flat-primary acms-admin-btn-block"
                  onClick={handleClick}
                >
                  <span className="acms-admin-icon acms-admin-icon-config_links" /> {ACMS.i18n('preview.get_link')}
                </button>
              </p>
            )}
            {status === 'waiting' && (
              <div style={{ position: 'relative', height: '50px' }}>
                <Spinner size={20} />
              </div>
            )}
            {status === 'done' && (
              <div className="acms-admin-form-group">
                <div className="acms-admin-form">
                  <div className="acms-admin-form-action">
                    <input
                      type="url"
                      className="acms-admin-form-width-full"
                      value={shareUrl}
                      ref={setInputRef}
                      readOnly
                    />
                    <span className="acms-admin-form-side-btn">
                      <button type="button" className="acms-admin-btn" onClick={handleCopy}>
                        {ACMS.i18n('preview.copy')}
                      </button>
                    </span>
                  </div>
                </div>
              </div>
            )}
            <p id="acms-admin-preview-share-modal-description">{ACMS.i18n('preview.get_link_detail')}</p>
            <p>
              （{ACMS.i18n('preview.expiration')}:{ACMS.Config.urlPreviewExpire} {ACMS.i18n('preview.hours')}）
            </p>
          </div>
        </Modal.Body>
      </Modal>
      <Notify
        message={ACMS.i18n('preview.copy_to_clipboard')}
        show={copied}
        onFinish={() => reset()}
        container={container}
      />
    </>
  );
};

export default PreviewShareModal;
