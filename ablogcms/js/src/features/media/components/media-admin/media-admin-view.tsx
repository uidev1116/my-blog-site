import { Component, createRef } from 'react';

import MediaList from '../media-list/media-list';
import MediaModal from '../media-modal/media-modal';
import MediaUploader from '../media-uploader/media-uploader';
import { MediaStateProps } from '../../types';
import { Tabs, TabPanel } from '../../../../components/tabs/tabs';

interface MediaAdminViewProps extends MediaStateProps {
  mode: 'edit' | 'insert';
  tab?: 'upload' | 'select';
}

interface MediaAdminViewState {
  isMediaModalOpen: boolean;
}

export default class MediaAdminView extends Component<MediaAdminViewProps, MediaAdminViewState> {
  uploaderRef: React.RefObject<InstanceType<typeof MediaUploader>>;

  constructor(props: MediaAdminViewProps) {
    super(props);
    const params = new URLSearchParams(window.location.hash.replace('#', ''));
    // e.g http://acms.org/bid/1/admin/media_index/#label=aaa,bbb&upload=true
    if (params.has('label')) {
      props.actions.setLabel(params.get('label') as string);
    }
    this.state = {
      isMediaModalOpen: false,
    };
    this.uploaderRef = createRef<InstanceType<typeof MediaUploader>>();
  }

  defaultTabIndex() {
    const params = new URLSearchParams(window.location.hash.replace('#', ''));
    const { tab } = this.props;
    if (params.get('upload') === 'true') {
      return 1;
    }

    if (tab === 'upload') {
      return 1;
    }
    return 0;
  }

  componentDidMount() {
    this.props.actions.setFormToken(window.csrfToken);
  }

  componentDidUpdate(prevProps: MediaAdminViewProps) {
    if (prevProps.item !== this.props.item && this.props.item !== null) {
      this.setState({ isMediaModalOpen: true });
    }
  }

  handleMediaModalClose() {
    this.setState({ isMediaModalOpen: false });
  }

  handleAfterMediaModalClose() {
    this.props.actions.setItem(null);
  }

  handleTabChange() {
    if (this.uploaderRef.current) {
      // タブ切り替え時にアップロード済みアイテムを削除
      this.uploaderRef.current.removeUploadedItems();
    }
  }

  render() {
    const { isMediaModalOpen } = this.state;
    const {
      items,
      actions,
      item,
      largeSize,
      formToken,
      archives,
      extensions,
      mode,
      lastPage,
      config,
      tags,
      total,
      selectedTags,
    } = this.props;

    return (
      <div>
        <Tabs defaultIndex={this.defaultTabIndex()} onChange={this.handleTabChange.bind(this)}>
          <TabPanel label={ACMS.i18n('media.media_list')} id="media-list">
            <MediaList
              items={items}
              actions={actions}
              mode={mode}
              lastPage={lastPage}
              config={config}
              archives={archives}
              tags={tags}
              extensions={extensions}
              total={total}
              selectedTags={selectedTags}
            />
          </TabPanel>
          <TabPanel label={ACMS.i18n('media.upload')} id="media-upload">
            <MediaUploader ref={this.uploaderRef} config={config} actions={actions} largeSize={largeSize} tags={tags} />
          </TabPanel>
        </Tabs>
        <MediaModal
          isOpen={isMediaModalOpen}
          onClose={this.handleMediaModalClose.bind(this)}
          item={item}
          actions={actions}
          formToken={formToken}
          tags={tags}
          onAfterClose={this.handleAfterMediaModalClose.bind(this)}
        />
      </div>
    );
  }
}
