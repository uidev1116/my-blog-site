import { createStore, applyMiddleware } from 'redux';
import { Provider } from 'react-redux';
import React from 'react';
import createSagaMiddleware from 'redux-saga';

import MediaUpdate from '../containers/media-update';
import reducer from '../reducers/media';
import rootSaga from '../sagas/media';

const sagaMiddleware = createSagaMiddleware();
const store = createStore(reducer, applyMiddleware(sagaMiddleware));

sagaMiddleware.run(rootSaga);

type MediaUpdateStore = {
  mid: string;
  onClose: () => void;
  onUpdate: (item: any) => void; // eslint-disable-line @typescript-eslint/no-explicit-any
};

export default ({ mid, onClose, onUpdate }: MediaUpdateStore) => (
  <Provider store={store}>
    <MediaUpdate mid={mid} onClose={onClose} onUpdate={onUpdate} />
  </Provider>
);
