import * as React from 'react';
import styled from 'styled-components';
import { AdminCardLane, AdminCardProps } from '../types/admin-menu';

const RemoveBtn = styled.button`
  position: absolute;
  top: 8px;
  right: 5px;
  background-color: transparent;
  border: none;
  appearance: none;
  font-size: 16px;
  color: #b9b9b9;
  &:hover {
    color: #797979;
  }
`;

const Title = styled.div<{
  first: boolean;
}>`
  position: relative;
  font-weight: bold;
  font-size: 11px;
  line-height: 1.3;
  display: inline-block;
  padding: 0;
  transition: background-color linear 0.15s;
  ${(props) => !props.first
    && `
  `}
`;

type AdminLaneHeaderProps = {
  title: string;
  titleStyle: React.CSSProperties;
  index: number;
  id: string;
  cards: AdminCardProps[];
  addLane: () => void;
  removeLane: (id: string) => void;
  doneEdit: (lane: AdminCardLane) => void;
};

type AdminLaneHeaderState = {
  editMode: boolean;
  title: string;
};

export default class AdminLaneHeader extends React.Component<AdminLaneHeaderProps, AdminLaneHeaderState> {
  constructor(props) {
    super(props);
    this.state = {
      editMode: false,
      title: props.title,
    };
  }

  edit = () => {
    this.setState({
      editMode: true,
    });
  };

  doneEdit = () => {
    const {
      id, index, cards, doneEdit,
    } = this.props;
    const { title } = this.state;
    this.setState({
      editMode: false,
    });
    doneEdit({
      id,
      index,
      title,
      cards,
    });
  };

  editTitle = (title: string) => {
    const {
      id, index, cards, doneEdit,
    } = this.props;
    this.setState({ title });
    doneEdit({
      id,
      index,
      title,
      cards,
    });
  };

  addLane = () => {
    const { addLane } = this.props;
    addLane();
  };

  removeLane(id: string) {
    const { removeLane } = this.props;
    removeLane(id);
  }

  render() {
    const {
      title, titleStyle, index, id,
    } = this.props;
    const { editMode } = this.state;
    return (
      <div>
        <span className="acms-admin-icon-sort react-trello-lane-sort" />
        {index !== 0 && (
          <RemoveBtn
            type="button"
            onClick={() => {
              this.removeLane(id);
            }}
          >
            ×
          </RemoveBtn>
        )}
        {!editMode && (
          <Title
            style={titleStyle}
            first={index === 0}
            onClick={() => {
              if (index !== 0) {
                this.edit();
              }
            }}
          >
            <span className="react-trello-lane-title-inner">
              {title}
              <span className="acms-admin-icon-control-edit react-trello-lane-title-edit" />
            </span>
          </Title>
        )}
        {editMode && (
          <input
            type="text"
            defaultValue={title}
            onInput={(e) => {
              this.editTitle(e.target.value);
            }}
          />
        )}
        {index === 0 && (
          <button
            type="button"
            className="acms-admin-btn acms-admin-btn-success"
            onClick={this.addLane}
            style={{ position: 'absolute', top: '5px', right: '10px' }}
          >
            {ACMS.i18n('admin_menu.add_row')}
          </button>
        )}
        {editMode && (
          <button type="button" className="acms-admin-btn react-trello-lane-done-btn" onClick={this.doneEdit}>
            {ACMS.i18n('admin_menu.complete')}
          </button>
        )}
      </div>
    );
  }
}
