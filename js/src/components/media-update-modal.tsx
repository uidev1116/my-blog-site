import React, { Component, Fragment } from 'react';
import axios from 'axios';
import MediaModal from '../components/media-modal';
import * as actions from '../actions/media';
import { MediaItem, MediaAjaxConfig } from '../types/media';

type MediaBasicProp = {
  mid: string,
  item: MediaItem,
  actions: typeof actions,
  formToken: string,
  config: MediaAjaxConfig,
  onClose: Function
}

export default class MediaBasic extends Component<MediaBasicProp> {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    const { mid } = this.props;
    const url = ACMS.Library.acmsLink({
      tpl: 'ajax/edit/media-edit.json',
      bid: ACMS.Config.bid,
      Query: {
        cache: new Date().getTime()
      }
    }, false);
    axios.get(url, {
      params: {
        _mid: mid
      }
    }).then((res) => {
      this.props.actions.setItem(res.data.item);
    });
    const tagUrl = ACMS.Library.acmsLink({
      tpl: 'ajax/edit/media-tag.json',
      bid: ACMS.Config.bid,
      Query: {
        cache: new Date().getTime()
      }
    }, false);
    axios.get(tagUrl).then((res) => {
      this.props.actions.setMediaTags(res.data);
    });
  }

  // componentWillReceiveProps(props) {
  //   if (!props.item && this.props.onClose) {
  //     this.props.onClose();
  //   }
  // }

  render() {
    const { actions, item, formToken, config, tags, onClose, onUpdate, updateMediaList } = this.props;

    return (
      <Fragment>
        {item && <MediaModal
          className="acms-admin-media-modal"
          item={item}
          actions={actions}
          formToken={formToken}
          config={config}
          tags={tags}
          onUpdate={onUpdate}
          onClose={onClose}
          updateMediaList={updateMediaList}
        />
        }
      </Fragment>
    );
  }
}
