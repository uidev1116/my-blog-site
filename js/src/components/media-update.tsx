import { createStore, applyMiddleware } from 'redux';
import { Provider } from 'react-redux';
import React from 'react';
import createSagaMiddleware from 'redux-saga';

import MediaUpdate from '../containers/media-update';
import reducer from '../reducers/media';
import rootSaga from '../sagas/media';

const sagaMiddleware = createSagaMiddleware();
const store = createStore(
  reducer,
  applyMiddleware(sagaMiddleware)
);

sagaMiddleware.run(rootSaga);

type MediaUpdate = {
  mid: string,
  onClose: Function,
  onUpdate: Function
}

export default ({
  mid, onClose, onUpdate
}: MediaUpdate) => (
  <Provider store={store}>
    <MediaUpdate mid={mid} onClose={onClose} onUpdate={onUpdate} />
  </Provider>
);
