import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export function noWhiteSpaceOnly(): ValidatorFn {
  return (control: AbstractControl): ValidationErrors | null => {
    const inputValue = control.value as string;
    return inputValue && inputValue.trim().length === 0 ? { noWhiteSpaceOnly: { value: control.value }} : null;
  };
}
