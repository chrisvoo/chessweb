import {Component, DestroyRef, inject} from '@angular/core';
import { PageComponent } from '../../page/page.component';
import {Button} from "primeng/button";
import {ConfirmDialogModule} from "primeng/confirmdialog";
import {Dialog} from "primeng/dialog";
import {Divider} from "primeng/divider";
import {AbstractControl, FormBuilder, FormGroup, ReactiveFormsModule, Validators} from "@angular/forms";
import {IconField} from "primeng/iconfield";
import {InputIcon} from "primeng/inputicon";
import {InputText} from "primeng/inputtext";
import {Message} from "primeng/message";
import {ConfirmationService, MessageService} from "primeng/api";
import {TableModule} from "primeng/table";
import {Toast} from "primeng/toast";
import {TooltipModule} from "primeng/tooltip";
import {NamedEntity} from '../../../types/models';
import {noWhiteSpaceOnly} from '../../validators/no-whitespace-only';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {CategoriesService} from '../../services/categories/categories.service';
import {ErrorResponse} from '../../../types/requests';

@Component({
  selector: 'admin-categories',
  imports: [
    TableModule, Button, IconField, InputIcon, InputText,
    TooltipModule, Dialog, ReactiveFormsModule, Message, Divider, Toast,
    ConfirmDialogModule, PageComponent
  ],
  providers: [MessageService, ConfirmationService],
  templateUrl: './admin-categories.component.html',
  styleUrl: './admin-categories.component.css',
  standalone: true,
})
export class AdminCategoriesComponent {
  sortOrder = 1
  categories: NamedEntity[] = []
  errorMessage: string = ''
  error: boolean = false;
  isDialogVisible = false
  dialogHeader = ''
  targetCategory?: NamedEntity
  categoryForm: FormGroup
  loadingResponse = false

  #destroyRef = inject(DestroyRef);

  constructor(
    private categoriesService: CategoriesService,
    private formBuilder: FormBuilder,
    private messageService: MessageService,
    private confirmationService: ConfirmationService
  ) {
    this.categoryForm = this.formBuilder.group({
      name: ['', Validators.compose([
        Validators.required,
        noWhiteSpaceOnly()
      ])]
    })
  }

  ngOnInit(): void {
    this.loadCategories();
  }

  #resetResponseStatusFields(): void {
    this.error = false;
    this.errorMessage = ''
  }

  loadCategories(): void {
    this.#resetResponseStatusFields();

    this.categoriesService.listCategories({
      all_items: true,
      sort_by: 'name',
      sort_order: this.sortOrder === 1 ? 'asc' : 'desc',
    }).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        this.error = true
        this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
        return throwError(() => err)
      })
    ).subscribe((res) => {
      this.categories = res.data.items
    })
  }

  hasFormErrors(): boolean | undefined {
    return this.name?.invalid && (this.name?.dirty || this.name?.touched);
  }

  hasServerError(): boolean {
    return this.error && this.errorMessage != ''
  }

  get name(): AbstractControl<any, any> | null {
    return this.categoryForm.get('name')
  }

  onModifyItem(item: NamedEntity): void {
    this.targetCategory = item
    this.categoryForm.patchValue({ name: item.name })
    this.dialogHeader = 'Modifica categoria'
    this.isDialogVisible = true;
  }

  onDeleteItem(item: NamedEntity): void {
    this.targetCategory = item
    this.confirmationService.confirm({
      header: 'Cancellazione categoria',
      message: `Vuoi cancellare ${this.targetCategory?.name}?`,
      accept: this.deleteCategory,
      icon: 'pi pi-question-circle',
    })
  }

  onCreateCategory(): void {
    this.categoryForm.patchValue({ name: '' })
    this.dialogHeader = 'Crea categoria'
    this.targetCategory = {
      id: null,
      name: '',
      created_at: '',
      updated_at: '',
    }
    this.isDialogVisible = true;
  }

  updateCategory(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    if (this.categoryForm.valid) {
      const category = { ...this.targetCategory! }
      category.name = this.categoryForm.get('name')?.value;
      this.categoriesService.updateCategory(category).
      pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          const errResponse = err.error as ErrorResponse
          if (
            errResponse.statusCode === 400 &&
            errResponse.data.code === 100
          ) {
            this.errorMessage = 'La categoria specificata esiste già';
          }

          this.loadingResponse = false;
          return throwError(() => err)
        })
      ).subscribe((res) => {
        this.loadCategories()
        this.messageService.add({
          severity: 'success',
          summary: 'Categoria aggiornata con successo'
        })
        this.isDialogVisible = false
        this.loadingResponse = false;
      })
    } else {
      console.error('invalid form!?')
    }
  }

  createCategory(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    if (this.categoryForm.valid) {
      const tagName = this.categoryForm.get('name')?.value
      this.categoriesService.createCategory(tagName).
      pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          const errResponse = err.error as ErrorResponse
          if (
            errResponse.statusCode === 400 &&
            errResponse.data.code === 100
          ) {
            this.errorMessage = 'La categoria specificata esiste già';
          }

          this.loadingResponse = false;
          return throwError(() => err)
        })
      ).subscribe((res) => {
        this.loadCategories()
        this.messageService.add({
          severity: 'success',
          summary: 'Categoria creata con successo'
        })
        this.isDialogVisible = false
        this.loadingResponse = false;
      })
    }
  }

  deleteCategory(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    this.categoriesService.deleteCategory(this.targetCategory!.id!)
      .pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          this.loadingResponse = false;
          return throwError(() => err)
        })
      ).subscribe(() => {
      this.loadCategories()
      this.messageService.add({
        severity: 'success',
        summary: 'Categoria cancellata con successo'
      })
      this.loadingResponse = false;
      this.closeConfirmationService()
    })
  }

  closeConfirmationService(): void {
    this.confirmationService.close()
  }
}
