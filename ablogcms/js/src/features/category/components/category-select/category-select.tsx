import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { InputActionMeta, SelectInstance } from 'react-select';
import { debounce } from 'throttle-debounce';
import styled from 'styled-components';
import RichSelect from '../../../../components/rich-select/rich-select';
import useCategoryOptionsSWR from '../../hooks/use-category-options-swr';
import type { CategoryOption, CreatedCategoryDTO } from '../../types';
import CategoryCreateModal from '../category-create-modal/category-create-modal';

interface CategorySelectProps
  extends Partial<
    Pick<
      React.ComponentPropsWithoutRef<typeof RichSelect>,
      'id' | 'inputId' | 'isDisabled' | 'form' | 'name' | 'isClearable' | 'menuPortalTarget'
    >
  > {
  defaultValue?: number;
  narrowDown?: boolean;
  isCreatable?: boolean;
  customOptions?: CategoryOption[];
  onChange?: (value: CategoryOption | null) => void;
}

const CategorySelectContainer = styled.div`
  display: flex;
  gap: 4px;
  align-items: center;
`;

const CategorySelectFormArea = styled.div`
  flex: 1;
`;

const CategorySelect = ({
  defaultValue: defaultValueProp,
  narrowDown = false,
  isCreatable = false,
  isClearable = true,
  customOptions = [],
  onChange,
  ...props
}: CategorySelectProps) => {
  const selectRef = useRef<SelectInstance<CategoryOption, false>>(null);
  const [inputValue, setInputValue] = useState<string>('');
  const [keyword, setKeyword] = useState<string>('');

  const [value, setValue] = useState<CategoryOption | null>(null);
  const [defaultValue, setDefaultValue] = useState<CategoryOption | null>(null);

  const currentCid = useMemo(() => {
    if (value !== null) {
      return parseInt(value.value, 10);
    }
    return defaultValueProp;
  }, [value, defaultValueProp]);
  const { options: apiOptions, isLoading } = useCategoryOptionsSWR({ keyword, narrowDown, currentCid });

  const options = useMemo(() => [...customOptions, ...(apiOptions || [])], [apiOptions, customOptions]);

  const handleChange = useCallback(
    (newValue: CategoryOption | null) => {
      setValue(newValue);
      onChange?.(newValue);
    },
    [onChange]
  );

  useEffect(() => {
    if (defaultValue === null && options && options.length > 0) {
      // カテゴリーのデフォルト値を設定
      // カテゴリーのデータ（option）は、サーバーから現在選択しているカテゴリーが含まれていることが保証されている前提
      setDefaultValue(options.find((option) => option.value === defaultValueProp?.toString()) || null);
    }
  }, [options, defaultValue, defaultValueProp]);

  useEffect(() => {
    setValue(defaultValue);
  }, [defaultValue]);

  const setKeywordDebounced = useRef(debounce(800, (keyword) => setKeyword(keyword))).current;

  const handleInputChange = useCallback(
    (newValue: string, meta: InputActionMeta) => {
      if (!['input-blur', 'menu-close'].includes(meta.action)) {
        setInputValue(newValue);
        setKeywordDebounced(newValue);
      }
    },
    [setKeywordDebounced]
  );

  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

  const handleCreateModalControlButtonClick = useCallback(() => {
    setIsCreateModalOpen(true);
  }, []);

  const handleCloseCreateModal = useCallback(() => {
    setIsCreateModalOpen(false);
  }, []);

  const handleCreate = useCallback((category: CreatedCategoryDTO) => {
    if (selectRef.current) {
      selectRef.current.setValue(
        {
          label: category.name,
          value: category.id.toString(),
        },
        'select-option'
      );
      setIsCreateModalOpen(false);
    }
  }, []);

  return (
    <CategorySelectContainer>
      <CategorySelectFormArea>
        <RichSelect<CategoryOption, false>
          ref={selectRef}
          value={value}
          inputValue={inputValue}
          isSearchable
          isClearable={isClearable}
          options={options}
          placeholder={ACMS.i18n('category.select_placeholder')}
          noOptionsMessage={() => ACMS.i18n('category.select_notfound')}
          isLoading={!!keyword && isLoading}
          onInputChange={handleInputChange}
          onChange={handleChange}
          {...props}
        />
      </CategorySelectFormArea>
      {isCreatable && (
        <>
          <div id="entry-create-category-display">
            <button type="button" className="acms-admin-btn-admin" onClick={handleCreateModalControlButtonClick}>
              {ACMS.i18n('category.add')}
            </button>
          </div>
          <CategoryCreateModal isOpen={isCreateModalOpen} onClose={handleCloseCreateModal} onCreate={handleCreate} />
        </>
      )}
    </CategorySelectContainer>
  );
};

export default CategorySelect;
