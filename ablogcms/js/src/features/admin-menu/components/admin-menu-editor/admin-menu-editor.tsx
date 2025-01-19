import Board from '@appleple/react-trello';
import { Component, Fragment } from 'react';
import AdminNewCard from '../admin-new-card/admin-new-card';
import AdminCard from '../admin-card/admin-card';
import AdminLaneHeader from '../admin-lane-header/admin-lane-header';
import type { AdminCardLaneType, AdminCardType, AdminBoardDataType } from '../../types';
import { random } from '../../../../utils';

interface AdminMenuProps {
  data: AdminBoardDataType;
}

interface AdminMenuState {
  data: AdminBoardDataType;
  showNotify: boolean;
  firstEdit: boolean;
  didMount: boolean;
}

export default class AdminMenuEditor extends Component<AdminMenuProps, AdminMenuState> {
  constructor(props: AdminMenuProps) {
    super(props);
    this.state = {
      data: props.data,
      showNotify: false,
      firstEdit: true,
      didMount: false,
    };
  }

  doneEdit = (newCard: AdminCardType) => {
    const { data } = this.state;
    const newData = {
      ...data,
      lanes: data.lanes.map((lane) =>
        lane.id === newCard.laneId
          ? { ...lane, cards: lane.cards.map((card) => (card.id === newCard.id ? newCard : card)) }
          : lane
      ),
    };
    this.onDataChange(newData);
  };

  doneLaneEdit = (newLane: AdminCardLaneType) => {
    const { data } = this.state;
    const newData = {
      ...data,
      lanes: data.lanes.map((lane) => (lane.id === newLane.id ? newLane : lane)),
    };
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
          movable: true,
          title: '新規',
          index: 1,
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
    if (confirm(ACMS.i18n('admin_menu.lane_remove_confirm'))) {
      this.onDataChange({
        lanes: [...lanes.slice(0, laneIndex), ...lanes.slice(laneIndex + 1)],
      });
    }
  };

  onDataChange(data: AdminBoardDataType) {
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
        <Board
          data={data}
          draggable
          editable
          style={{ border: '1px solid #F1F1F1', backgroundColor: '#FFF' }}
          canAddLanes
          addLaneTitle={ACMS.i18n('admin_menu.add_lane')}
          addCardTitle={ACMS.i18n('admin_menu.add_card')}
          hideCardDeleteIcon
          // @ts-expect-error Bordコンポーネントが自動的にpropsを渡すため
          newCardTemplate={<AdminNewCard />}
          customLaneHeader={
            // @ts-expect-error Bordコンポーネントが自動的にpropsを渡すため
            <AdminLaneHeader doneEdit={this.doneLaneEdit} addLane={this.addLane} removeLane={this.removeLane} />
          }
          customCardLayout
          handleLaneDragStart={() => false}
          onDataChange={(currentData) => {
            this.onDataChange(currentData);
          }}
        >
          {/* @ts-expect-error Bordコンポーネントが自動的にpropsを渡すため */}
          <AdminCard doneEdit={this.doneEdit} />
        </Board>
        {data.lanes.map((lane) => (
          <Fragment key={lane.id}>
            <input type="hidden" name="admin_menu_lane_title[]" value={lane.title} />
            <input type="hidden" name="admin_menu_lane_id[]" value={lane.id} />
            {lane.cards.map((card) => (
              <Fragment key={card.id}>
                <input type="hidden" name="admin_menu_card_title[]" value={card.title} />
                <input type="hidden" name="admin_menu_card_url[]" value={card.url} />
                <input type="hidden" name="admin_menu_card_laneid[]" value={lane.id} />
                <input type="hidden" name="admin_menu_card_id[]" value={card.id} />
                <input type="hidden" name="admin_menu_card_icon[]" value={card.icon} />
                <input type="hidden" name="admin_menu_card_admin[]" value={`${card.admin}`} />
              </Fragment>
            ))}
          </Fragment>
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
