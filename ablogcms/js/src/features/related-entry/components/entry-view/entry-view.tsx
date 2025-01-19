import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import classNames from 'classnames';
import DraggableButton from '../../../../components/draggable-button/draggable-button';

import type { RelatedEntryType } from '../../types';

interface RelatedEntryViewProps {
  entry: RelatedEntryType;
  onRemove: (entryId: number) => void;
}

const RelatedEntryView = ({ entry, onRemove }: RelatedEntryViewProps) => {
  const { attributes, listeners, setNodeRef, transform, transition, setActivatorNodeRef, isDragging } = useSortable({
    id: entry.id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <li
      ref={setNodeRef}
      style={style}
      className={classNames({
        'acms-admin-dragging': isDragging,
      })}
    >
      <div className={classNames('acms-admin-related-entry')}>
        <div className="acms-admin-related-entry-handler-item">
          <DraggableButton ref={setActivatorNodeRef} {...attributes} {...listeners} />
        </div>
        {entry.image && entry.image.length > 5 && (
          <div className="acms-admin-related-entry-image-item">
            <img
              className="acms-admin-related-entry-image"
              src={entry.image}
              alt={entry.title}
              width={50}
              height={28}
            />
          </div>
        )}
        <div className="acms-admin-related-entry-title-item">
          <div className="acms-admin-related-entry-title">
            {entry.title}
            {entry.categoryName && (
              <span className="acms-admin-icon-mute">
                <span
                  className="acms-admin-icon-category acms-admin-margin-right-mini acms-admin-margin-left-mini"
                  aria-hidden
                />
                {entry.categoryName}
              </span>
            )}
          </div>
        </div>
        <div className="acms-admin-related-entry-action-item">
          <button
            type="button"
            className="acms-admin-btn-admin acms-admin-btn-admin-danger"
            onClick={() => {
              onRemove(entry.id);
            }}
          >
            {ACMS.i18n('related_entry.remove')}
          </button>
          <a href={entry.url} className="acms-admin-btn-admin" target="_blank" rel="noopener noreferrer">
            {ACMS.i18n('related_entry.check')}
          </a>
        </div>
      </div>
    </li>
  );
};

export default RelatedEntryView;
