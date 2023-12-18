import { createStore, applyMiddleware } from 'redux';
import { Provider } from 'react-redux';
import React from 'react';
import createSagaMiddleware from 'redux-saga';

import MediaInsertModal from '../containers/media-insert';
import reducer from '../reducers/media';
import rootSaga from '../sagas/media';
import { MediaItem } from '../types/media';

const sagaMiddleware = createSagaMiddleware();
const store = createStore(reducer, applyMiddleware(sagaMiddleware));

sagaMiddleware.run(rootSaga);

type MediaInsertType = {
  onClose: () => void;
  onInsert(items: MediaItem[]): void;
  tab: 'upload' | 'select';
  onSelect?: () => void;
  mode?: string;
  radioMode?: boolean;
  filetype?: 'all' | 'file' | 'image';
};

export default ({
  mode = 'insert',
  onClose,
  onInsert,
  onSelect,
  tab,
  files,
  radioMode,
  filetype = 'all',
}: MediaInsertType) => (
  <Provider store={store}>
    <MediaInsertModal
      mode={mode}
      radioMode={radioMode}
      onSelect={onSelect}
      onClose={onClose}
      onInsert={onInsert}
      tab={tab}
      files={files}
      filetype={filetype}
    />
  </Provider>
);
