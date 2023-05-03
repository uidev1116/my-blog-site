import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as Actions from '../actions/media';
import MediaBasic from '../components/media-basic';

function mapStateToProps(state) {
  return state;
}

function mapDispatchToProps(dispatch) {
  return {
    actions: bindActionCreators(Actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(MediaBasic);
