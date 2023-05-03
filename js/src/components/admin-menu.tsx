import * as React from 'react';
import Board from '@appleple/react-trello';
import { setIn } from 'immutable';
import { createGlobalStyle } from 'styled-components';
import AdminNewCard from './admin-new-card';
import AdminCard from './admin-card';
import AdminLaneHeader from './admin-lane-header';
import { AdminCardLane, AdminCardProps } from '../types/admin-menu';
import { random } from '../lib/utility';

interface AdminMenuProps {
  data: {
    lanes: AdminCardLane[];
  };
}

interface AdminMenuState {
  data: {
    lanes: AdminCardLane[];
  };
  showNotify: boolean;
  firstEdit: boolean;
}

const GlobalStyle = createGlobalStyle`
  #js-admin-menu-edit .smooth-dnd-container > span:first-child > .react-trello-lane {
    background-color: #fbfbfb;
    border: 1px solid #EEE;
  }
  .react-trello-card {
    border: 1px solid #CCC;
  }
`;

export default class AdminMenu extends React.Component<AdminMenuProps, AdminMenuState> {
  constructor(props) {
    super(props);
    this.state = {
      data: props.data,
      showNotify: false,
      firstEdit: true,
      didMount: false,
    };
  }

  doneEdit = (item: AdminCardProps) => {
    const { data } = this.state;
    const laneIndex = data.lanes.findIndex((lane) => lane.id === item.laneId);
    const newData = setIn(data, ['lanes', laneIndex, 'cards', item.index], item);
    this.onDataChange(newData);
  };

  doneLaneEdit = (item: AdminCardLane) => {
    const { data } = this.state;
    const newData = setIn(data, ['lanes', item.index], item);
    this.onDataChange(newData);
  };

  addLane = () => {
    const { data } = this.state;
    const { lanes } = data;

    this.onDataChange({
      lanes: [
        lanes[0],
        {
          id: random(),
          draggable: true,
          title: '新規',
          cards: [],
        },
        ...lanes.slice(1),
      ],
    });
  };

  removeLane = (id: string) => {
    const { data } = this.state;
    const { lanes } = data;
    const laneIndex = lanes.findIndex((lane) => lane.id === id);
    const lane = lanes[laneIndex];
    const { cards } = lane;
    if (cards.length) {
      alert(ACMS.i18n('admin_menu.lane_alert'));
      return;
    }
    if (confirm(ACMS.i18n('admin_menu.menu_remove_confirm'))) {
      this.onDataChange({
        lanes: [...lanes.slice(0, laneIndex), ...lanes.slice(laneIndex + 1)],
      });
    }
  };

  onDataChange(data) {
    const { firstEdit, didMount } = this.state;
    if (!didMount) {
      this.setState({ data, didMount: true });
      return;
    }
    const showNotify = !!firstEdit;
    if (showNotify) {
      $('.js-config-not-saved').addClass('active');
      this.setState({ data, firstEdit: false, showNotify });
      return;
    }
    this.setState({ data });
  }

  render() {
    const { data } = this.state;

    return (
      <>
        <GlobalStyle />
        <Board
          data={data}
          draggable
          editable
          style={{ border: '1px solid #F1F1F1', backgroundColor: '#FFF' }}
          canAddLanes
          addLaneTitle={ACMS.i18n('admin_menu.add_lane')}
          addCardTitle={ACMS.i18n('admin_menu.add_title')}
          hideCardDeleteIcon
          newCardTemplate={<AdminNewCard />}
          customLaneHeader={
            <AdminLaneHeader doneEdit={this.doneLaneEdit} addLane={this.addLane} removeLane={this.removeLane} />
          }
          customCardLayout
          handleLaneDragStart={() => false}
          onDataChange={(currentData) => {
            this.onDataChange(currentData);
          }}
        >
          <AdminCard doneEdit={this.doneEdit} />
        </Board>
        {data.lanes.map((lane) => (
          <>
            <input type="hidden" name="admin_menu_lane_title[]" value={lane.title} />
            <input type="hidden" name="admin_menu_lane_id[]" value={lane.id} />
            {lane.cards.map((card) => (
              <>
                <input type="hidden" name="admin_menu_card_title[]" value={card.title} />
                <input type="hidden" name="admin_menu_card_url[]" value={card.url} />
                <input type="hidden" name="admin_menu_card_laneid[]" value={lane.id} />
                <input type="hidden" name="admin_menu_card_id[]" value={card.id} />
                <input type="hidden" name="admin_menu_card_icon[]" value={card.icon} />
                <input type="hidden" name="admin_menu_card_admin[]" value={`${card.admin}`} />
              </>
            ))}
          </>
        ))}
        <input type="hidden" name="@admin_menu_lane_group[]" value="admin_menu_lane_title" />
        <input type="hidden" name="config[]" value="admin_menu_lane_title" />
        <input type="hidden" name="@admin_menu_lane_group[]" value="admin_menu_lane_id" />
        <input type="hidden" name="config[]" value="admin_menu_lane_id" />
        <input type="hidden" name="@admin_menu_card_group[]" value="admin_menu_card_title" />
        <input type="hidden" name="config[]" value="admin_menu_card_title" />
        <input type="hidden" name="@admin_menu_card_group[]" value="admin_menu_card_url" />
        <input type="hidden" name="config[]" value="admin_menu_card_url" />
        <input type="hidden" name="@admin_menu_card_group[]" value="admin_menu_card_laneid" />
        <input type="hidden" name="config[]" value="admin_menu_card_laneid" />
        <input type="hidden" name="@admin_menu_card_group[]" value="admin_menu_card_id" />
        <input type="hidden" name="config[]" value="admin_menu_card_id" />
        <input type="hidden" name="@admin_menu_card_group[]" value="admin_menu_card_icon" />
        <input type="hidden" name="config[]" value="admin_menu_card_icon" />
        <input type="hidden" name="@admin_menu_card_group[]" value="admin_menu_card_admin" />
        <input type="hidden" name="config[]" value="admin_menu_card_admin" />
        <input type="hidden" name="config[]" value="@admin_menu_lane_group" />
        <input type="hidden" name="config[]" value="@admin_menu_card_group" />
      </>
    );
  }
}
