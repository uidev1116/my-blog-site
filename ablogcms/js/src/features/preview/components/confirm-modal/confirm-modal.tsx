import Modal from '../../../../components/modal/modal';

interface ConfirmModalProps {
  isOpen: boolean;
  onClose: () => void;
  container: HTMLElement;
}

const ConfirmModal = ({ isOpen, onClose, container }: ConfirmModalProps) => (
  <Modal
    isOpen={isOpen}
    onClose={onClose}
    size="small"
    dialogStyle={{ marginTop: '100px' }}
    container={container}
    aria-labelledby="acms-admin-preview-confirm-modal-title"
    aria-describedby="acms-admin-preview-confirm-modal-description"
  >
    <Modal.Header>{ACMS.i18n('preview.preview_mode')}</Modal.Header>
    <Modal.Body>
      <p id="acms-admin-preview-confirm-modal-description">{ACMS.i18n('preview.confirm_txt')}</p>
    </Modal.Body>
  </Modal>
);

export default ConfirmModal;
