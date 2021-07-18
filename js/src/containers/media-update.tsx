import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as Actions from '../actions/media';
import MediaUpdate from '../components/media-update-modal';

function mapStateToProps(state) {
  return state;
}

function mapDispatchToProps(dispatch) {
  return {
    actions: bindActionCreators(Actions, dispatch)
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(MediaUpdate);
