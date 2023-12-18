import React, { Component } from 'react';
import axios from 'axios';
import MediaModal from './media-modal';
import * as actions from '../actions/media';
import { MediaItem, MediaAjaxConfig } from '../types/media';

type MediaBasicProp = {
  mid: string;
  item: MediaItem;
  actions: typeof actions;
  formToken: string;
  config: MediaAjaxConfig;
  onClose: () => void;
};

export default class MediaBasic extends Component<MediaBasicProp> {
  componentDidMount() {
    const { mid } = this.props;
    const url = ACMS.Library.acmsLink(
      {
        tpl: 'ajax/edit/media-edit.json',
        bid: ACMS.Config.bid,
        Query: {
          cache: new Date().getTime(),
        },
      },
      false,
    );
    axios
      .get(url, {
        params: {
          _mid: mid,
        },
      })
      .then((res) => {
        this.props.actions.setItem(res.data.item);
      });
    const tagUrl = ACMS.Library.acmsLink(
      {
        tpl: 'ajax/edit/media-tag.json',
        bid: ACMS.Config.bid,
        Query: {
          cache: new Date().getTime(),
        },
      },
      false,
    );
    axios.get(tagUrl).then((res) => {
      this.props.actions.setMediaTags(res.data);
    });
  }

  render() {
    const {
      actions, item, formToken, config, tags, onClose, onUpdate, updateMediaList,
    } = this.props;

    if (!item) {
      return null;
    }

    return (
      <MediaModal
        item={item}
        actions={actions}
        formToken={formToken}
        config={config}
        tags={tags}
        onUpdate={onUpdate}
        onClose={onClose}
        updateMediaList={updateMediaList}
      />
    );
  }
}
