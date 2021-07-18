import * as React from 'react';
import IconPicker from './icon-picker';
import icons from '../lib/icons';
import styled from 'styled-components';

interface AdminMenuCardState {
  title: string,
  label: string,
  icon: string,
  url: string,
  deletable: string
}

interface AdminNewCardProps {
  onAdd(state: AdminMenuCardState): void
  onCancel(): void
}

const CardTitle = styled.div`
  font-size: 14px;
`;

const AdminCardInner = styled.div`
  padding: 5px;
`;

const AdminCardRow = styled.div`
  margin-bottom: 8px;
`;

const AdminCard = styled.div`
  border-radius: 2px;
  background-color: #fff;
  position: relative;
  padding: 5px;
  cursor: pointer;
  margin: 1px 2px 7px 2px;
  min-width: 220px;
  box-shadow: 0 0 6px rgba(0,0,0,.2);
  
  &:hover {
    background: #fff;
  }
`;

export default class AdminNewCard extends React.Component<AdminNewCardProps, AdminMenuCardState> {

  handleAdd = () => {
    if (this.state.url && this.state.title) {
      this.props.onAdd(Object.assign({}, this.state ));
      return;
    }
    alert('タイトルとURLの両方を入力してください。');
  }

  render() {
    const { onCancel } = this.props
    return (
      <AdminCard>
        <div style={{ whiteSpace: 'normal' }} className="acms-admin-form react-trello-new">
          <AdminCardInner>
            <AdminCardRow>
              <span className="react-trello-card-item-name">{ACMS.i18n("admin_menu.add_icon")}</span>
              <div>
                <IconPicker icons={icons} onChange={(icon) => {
                  this.setState({ icon });
                }} />
              </div>
            </AdminCardRow>
            <AdminCardRow>
              <span className="react-trello-card-item-name">{ACMS.i18n("admin_menu.add_title")}</span>
              <div>
                <input type="text" placeholder={ACMS.i18n("admin_menu.add_title")} className="acms-admin-form-width-full" onInput={(e) => {
                  this.setState({
                    title: e.target.value
                  });
                }} />
              </div>
            </AdminCardRow>
            <AdminCardRow>
              <span className="react-trello-card-item-name">{ACMS.i18n("admin_menu.url")}</span>
              <div>
                <input type="text" placeholder="URL" className="acms-admin-form-width-full" onInput={(e) => {
                  this.setState({
                    url: e.target.value
                  });
                }} />
              </div>
            </AdminCardRow>
            <div className="clearfix">
              <button type="button" className="acms-admin-btn acms-admin-btn-success react-trello-card-add-btn" onClick={this.handleAdd}>{ACMS.i18n("admin_menu.add")}</button>
              <button type="button" className="acms-admin-btn acms-admin-btn-link react-trello-card-cancel-btn" onClick={onCancel}>{ACMS.i18n("admin_menu.cancel")}</button>
            </div>
          </AdminCardInner>
        </div>
      </AdminCard>
    )
  }
}
