import styled from 'styled-components';
import { Component } from 'react';
import type { AdminCardType } from '../../types';
import IconPicker from '../../../../components/icon-picker/icon-picker';

const CardTitle = styled.div`
  padding: 5px 50px 5px 5px;
  font-size: 14px;
  line-height: 1.8;
`;

const AdminCardInner = styled.div`
  padding: 5px;
`;

const AdminCardRow = styled.div`
  padding-bottom: 5px;
`;

interface AdminCardState {
  editMode: boolean;
  title: string;
  url: string;
  icon: string;
}

interface AdminCardProps extends AdminCardType {
  removeCard(id: string, laneId: string): void;
  onDelete(id: string, laneId: string): void;
  doneEdit(edit: AdminCardType): void;
}

export default class AdminCard extends Component<AdminCardProps, AdminCardState> {
  constructor(props: AdminCardProps) {
    super(props);
    this.state = {
      editMode: false,
      title: props.title,
      url: props.url,
      icon: props.icon,
    };
  }

  handleRemove = (event: React.MouseEvent<HTMLButtonElement>) => {
    if (confirm(ACMS.i18n('admin_menu.menu_remove_confirm'))) {
      const { id, laneId, removeCard, onDelete } = this.props;
      removeCard(laneId, id);
      onDelete(id, laneId);
      event.stopPropagation();
    }
  };

  editCard = () => {
    const { editMode } = this.state;
    this.setState({
      editMode: !editMode,
    });
  };

  doneEdit = () => {
    const { id, laneId, index, label, admin, doneEdit } = this.props;
    const { title, url, icon } = this.state;
    const { editMode } = this.state;
    if (!title && !url) {
      alert(ACMS.i18n('admin_menu.enter_both_title_and_url'));
      return;
    }
    this.setState({
      editMode: !editMode,
    });
    doneEdit({
      id,
      laneId,
      title,
      url,
      label,
      index,
      icon,
      admin,
    });
  };

  render() {
    const { id, admin } = this.props;
    const { editMode, title, url, icon } = this.state;
    return (
      <>
        {editMode && (
          <div className="acms-admin-form">
            <AdminCardInner>
              <AdminCardRow>
                <span className="react-trello-card-item-name">{ACMS.i18n('admin_menu.icon')}</span>
                <div>
                  <IconPicker
                    defaultValue={icon}
                    onChange={(nextIcon) => {
                      this.setState({ icon: nextIcon });
                    }}
                  />
                </div>
              </AdminCardRow>
              <AdminCardRow>
                <label htmlFor={`input-text-admin-menu-title-${id}`} className="react-trello-card-item-name">
                  {ACMS.i18n('admin_menu.title')}
                </label>
                <div>
                  <input
                    id={`input-text-admin-menu-title-${id}`}
                    type="text"
                    placeholder={ACMS.i18n('admin_menu.title')}
                    className="acms-admin-form-width-full"
                    value={title}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                      this.setState({ title: event.target.value });
                    }}
                    onKeyDown={(event: React.KeyboardEvent<HTMLInputElement>) => {
                      if (event.key === 'Enter' && !event.nativeEvent.isComposing) {
                        event.preventDefault();
                        this.doneEdit();
                      }
                    }}
                  />
                </div>
              </AdminCardRow>
              {!admin && (
                <AdminCardRow>
                  <label htmlFor={`input-text-admin-menu-url-${id}`} className="react-trello-card-item-name">
                    {ACMS.i18n('admin_menu.url')}
                  </label>
                  <div>
                    <input
                      id={`input-text-admin-menu-url-${id}`}
                      type="text"
                      placeholder="URL"
                      className="acms-admin-form-width-full"
                      value={url}
                      onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        this.setState({ url: event.target.value });
                      }}
                      onKeyDown={(event: React.KeyboardEvent<HTMLInputElement>) => {
                        if (event.key === 'Enter' && !event.nativeEvent.isComposing) {
                          event.preventDefault();
                          this.doneEdit();
                        }
                      }}
                    />
                  </div>
                </AdminCardRow>
              )}
              <div className="clearfix">
                <button type="button" className="acms-admin-btn acms-admin-float-right" onClick={this.doneEdit}>
                  {ACMS.i18n('admin_menu.complete')}
                </button>
                {!admin && (
                  <button
                    type="button"
                    className="acms-admin-btn acms-admin-btn-danger acms-admin-float-right"
                    style={{ marginRight: '10px' }}
                    onClick={this.handleRemove}
                  >
                    {ACMS.i18n('admin_menu.remove')}
                  </button>
                )}
              </div>
            </AdminCardInner>
          </div>
        )}
        {!editMode && (
          <CardTitle>
            <div className="react-trello-card-item">
              <span className="acms-admin-icon-sort react-trello-card-sort" />
              {!editMode && (
                <button type="button" className="acms-admin-btn react-trello-card-edit-btn" onClick={this.editCard}>
                  {ACMS.i18n('admin_menu.edit')}
                </button>
              )}
              <div className="react-trello-card-title">
                <span className="react-trello-card-icon">{icon && <span className={icon} aria-hidden />}</span>
                {title}
              </div>
            </div>
          </CardTitle>
        )}
      </>
    );
  }
}
