interface PreviewActionsProps {
  onCapture?: () => void;
  onShare?: () => void;
}

const PreviewActions = ({ onCapture, onShare }: PreviewActionsProps) => {
  if (!onShare && !onCapture) {
    return null;
  }

  return (
    <div className="acms-admin-preview-actions">
      {onCapture && (
        <div>
          <button
            type="button"
            onClick={onCapture}
            className="acms-admin-btn-unstyled"
            style={{ color: '#666', fontSize: '18px', cursor: 'pointer', padding: '5px', display: 'inline-flex' }}
            aria-label={ACMS.i18n('preview.capture')}
          >
            <span className="acms-admin-icon-config_entry_photo" aria-hidden />
          </button>
        </div>
      )}
      {onShare && (
        <div>
          <button
            type="button"
            className="acms-admin-btn-unstyled"
            onClick={onShare}
            style={{ color: '#666', fontSize: '18px', cursor: 'pointer', padding: '5px', display: 'inline-flex' }}
            aria-label={ACMS.i18n('preview.open_share_modal')}
          >
            <span className="acms-admin-icon-config_export" aria-hidden />
          </button>
        </div>
      )}
    </div>
  );
};

export default PreviewActions;
