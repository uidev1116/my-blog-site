import * as React from 'react';
import styled from 'styled-components';
import { AdminCardProps } from '../types/admin-menu';
import IconPicker from './icon-picker';
import icons from '../lib/icons';

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

type Edit = {
  id: string;
  laneId: string;
  title: string;
  url: string;
  index: number;
  icon: string;
  admin: boolean;
};

type UpdateTarget = 'icon' | 'url' | 'title';

type Props = AdminCardProps & {
  doneEdit(edit: Edit): void;
};

export default class AdminCard extends React.Component<Props, AdminCardState> {
  constructor(props) {
    super(props);
    this.state = {
      editMode: false,
      title: props.title,
      url: props.url,
      icon: props.icon,
    };
  }

  removeCard = (e) => {
    if (confirm(ACMS.i18n('admin_menu.menu_remove_confirm'))) {
      const {
        id, laneId, removeCard, onDelete,
      } = this.props;
      removeCard(laneId, id);
      onDelete(id, laneId);
      e.stopPropagation();
    }
  };

  editCard = () => {
    const { editMode } = this.state;
    this.setState({
      editMode: !editMode,
    });
  };

  doneEdit = () => {
    const {
      id, laneId, index, admin, doneEdit,
    } = this.props;
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
      index,
      icon,
      admin,
    });
  };

  updateCard(key: UpdateTarget, value: string) {
    const {
      id, laneId, index, admin, doneEdit,
    } = this.props;
    const newState = { [key]: value } as Pick<AdminCardState, UpdateTarget>;
    this.setState(newState, () => {
      const { title, url, icon } = this.state;
      doneEdit({
        id,
        laneId,
        title,
        url,
        index,
        icon,
        admin,
      });
    });
  }

  render() {
    const {
      title, url, admin, icon,
    } = this.props;
    const { editMode } = this.state;
    return (
      <>
        {editMode && (
          <div className="acms-admin-form">
            <AdminCardInner>
              <AdminCardRow>
                <span className="react-trello-card-item-name">{ACMS.i18n('admin_menu.icon')}</span>
                <div>
                  <IconPicker
                    icons={icons}
                    defaultValue={icon}
                    onChange={(nextIcon) => {
                      this.updateCard('icon', nextIcon);
                    }}
                  />
                </div>
              </AdminCardRow>
              <AdminCardRow>
                <span className="react-trello-card-item-name">{ACMS.i18n('admin_menu.title')}</span>
                <div>
                  <input
                    type="text"
                    placeholder={ACMS.i18n('admin_menu.title')}
                    className="acms-admin-form-width-full"
                    defaultValue={title}
                    onInput={(e) => {
                      this.updateCard('title', e.target.value);
                    }}
                  />
                </div>
              </AdminCardRow>
              {!admin && (
                <AdminCardRow>
                  <span className="react-trello-card-item-name">{ACMS.i18n('admin_menu.url')}</span>
                  <div>
                    <input
                      type="text"
                      placeholder="URL"
                      className="acms-admin-form-width-full"
                      defaultValue={url}
                      onInput={(e) => {
                        this.updateCard('url', e.target.value);
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
                    onClick={this.removeCard}
                  >
                    {ACMS.i18n('admin_menu.remove')}
                  </button>
                )}
              </div>
            </AdminCardInner>
          </div>
        )}
        {!editMode && (
          <>
            <CardTitle>
              <div className="react-trello-card-item">
                <span className="acms-admin-icon-sort react-trello-card-sort" />
                {!editMode && (
                  <button type="button" className="acms-admin-btn react-trello-card-edit-btn" onClick={this.editCard}>
                    {ACMS.i18n('admin_menu.edit')}
                  </button>
                )}
                <div className="react-trello-card-title">
                  <span className="react-trello-card-icon">{icon && <span className={icon} />}</span>
                  {title}
                </div>
              </div>
            </CardTitle>
            {/* <Detail style={{padding: '5px'}}>{url}</Detail> */}
          </>
        )}
      </>
    );
  }
}
