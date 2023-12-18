import Select, { Creatable, Async } from 'react-select';
import styled from 'styled-components';

export default styled(Select)`
  .Select-control {
    height: 30px;
  }
  .Select-input {
    height: 28px;
  }
  .Select-multi-value-wrapper {
    .Select-value {
      line-height: 1.2;
    }
  }
  .Select-input > input {
    padding: 5px 0 0;
    line-height: 1.2;
  }
`;

const StyledAsync = styled(Async)`
  .Select-control {
    height: 30px;
  }
  .Select-input {
    height: 28px;
  }
  .Select-multi-value-wrapper {
    .Select-value {
      line-height: 1.7;
    }
  }
  .Select-input > input {
    padding: 5px 0 0;
    line-height: 1.2;
  }
`;

const StyledCreatable = styled(Creatable)`
  .Select-control {
    height: 30px;
  }
  .Select-input {
    height: 28px;
  }
  .Select-multi-value-wrapper {
    .Select-value {
      line-height: 1.2;
    }
  }
  .Select-multi-value-wrapper > .Select-value {
    margin-top: 5px;
  }
  .Select-input > input {
    padding: 5px 0 0;
    line-height: 1.2;
  }
`;

export { StyledCreatable as Creatable, StyledAsync as Async };
