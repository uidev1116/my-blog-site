import { createStore, applyMiddleware } from 'redux';
import { Provider } from 'react-redux';
import React from 'react';
import createSagaMiddleware from 'redux-saga';

import Media from '../containers/media';
import reducer from '../reducers/media';
import rootSaga from '../sagas/media';

const sagaMiddleware = createSagaMiddleware();
const store = createStore(reducer, applyMiddleware(sagaMiddleware));

sagaMiddleware.run(rootSaga);

interface MediaEdit {
  mode: string;
  onSelect: () => void;
}

export default ({ mode = 'edit', onSelect }: MediaEdit) => (
  <Provider store={store}>
    <Media mode={mode} onSelect={onSelect} />
  </Provider>
);
