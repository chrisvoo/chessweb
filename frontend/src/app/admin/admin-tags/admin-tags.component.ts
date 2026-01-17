import { Component, DestroyRef, inject, OnInit } from '@angular/core';
import { TableModule } from 'primeng/table';
import { Button, ButtonDirective, ButtonIcon, ButtonLabel } from 'primeng/button';
import { ConfirmDialogModule } from 'primeng/confirmdialog';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { InputText } from 'primeng/inputtext';
import { TooltipModule } from 'primeng/tooltip';
import { TagsService } from '../../services/tags/tags.service';
import { catchError, throwError } from 'rxjs';
import { Dialog } from 'primeng/dialog';
import { AbstractControl, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Message } from 'primeng/message';
import { noWhiteSpaceOnly } from '../../validators/no-whitespace-only';
import { ErrorResponse } from '../../../types/requests';
import { Divider } from 'primeng/divider';
import { Toast } from 'primeng/toast';
import { ConfirmationService, MessageService } from 'primeng/api';
import { PageComponent } from '../../components/page/page.component';
import {NamedEntity} from '../../../types/models';

@Component({
  selector: 'admin-tags',
  imports: [
    TableModule, Button, IconField, InputIcon, InputText,
    TooltipModule, Dialog, ReactiveFormsModule, Message, Divider, Toast,
    ConfirmDialogModule, PageComponent
  ],
  providers: [MessageService, ConfirmationService],
  templateUrl: './admin-tags.component.html',
  styleUrl: './admin-tags.component.css',
  standalone: true
})
export class AdminTagsComponent implements OnInit {
  sortOrder = 1
  tags: NamedEntity[] = []
  errorMessage: string = ''
  error: boolean = false;
  isDialogVisible = false
  dialogHeader = ''
  targetTag?: NamedEntity
  tagForm: FormGroup
  loadingResponse = false

  #destroyRef = inject(DestroyRef);

  constructor(
    private tagsService: TagsService,
    private formBuilder: FormBuilder,
    private messageService: MessageService,
    private confirmationService: ConfirmationService
  ) {
    this.tagForm = this.formBuilder.group({
      name: ['', Validators.compose([
        Validators.required,
        noWhiteSpaceOnly()
      ])]
    })
  }

  ngOnInit(): void {
    this.loadTags();
  }

  #resetResponseStatusFields(): void {
    this.error = false;
    this.errorMessage = ''
  }

  loadTags(): void {
    this.#resetResponseStatusFields();

    this.tagsService.listTags({
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
      this.tags = res.data.items
    })
  }

  hasFormErrors(): boolean | undefined {
    return this.name?.invalid && (this.name?.dirty || this.name?.touched);
  }

  hasServerError(): boolean {
    return this.error && this.errorMessage != ''
  }

  get name(): AbstractControl<any, any> | null {
    return this.tagForm.get('name')
  }

  onModifyItem(item: NamedEntity): void {
    this.targetTag = item
    this.tagForm.patchValue({ name: item.name })
    this.dialogHeader = 'Modifica tag'
    this.isDialogVisible = true;
  }

  onDeleteItem(item: NamedEntity): void {
    this.targetTag = item
    this.confirmationService.confirm({
      header: 'Cancellazione tag',
      message: `Vuoi cancellare ${this.targetTag?.name}?`,
      accept: this.deleteTag,
      icon: 'pi pi-question-circle',
    })
  }

  onCreateTag(): void {
    this.tagForm.patchValue({ name: '' })
    this.dialogHeader = 'Crea tag'
    this.targetTag = {
      id: null,
      name: '',
      slug: '',
      created_at: '',
      updated_at: '',
    }
    this.isDialogVisible = true;
  }

  updateTag(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    if (this.tagForm.valid) {
      const tag = { ...this.targetTag! }
      tag.name = this.tagForm.get('name')?.value;
      this.tagsService.updateTag(tag).
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
              this.errorMessage = 'Il tag specificato esiste già';
            }

            this.loadingResponse = false;
            return throwError(() => err)
          })
        ).subscribe((res) => {
          this.loadTags()
          this.messageService.add({
            severity: 'success',
            summary: 'Tag aggiornato con successo'
          })
          this.isDialogVisible = false
          this.loadingResponse = false;
        })
    } else {
      console.error('invalid form!?')
    }
  }

  createTag(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    if (this.tagForm.valid) {
      const tagName = this.tagForm.get('name')?.value
      this.tagsService.createTag(tagName).
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
              this.errorMessage = 'Il tag specificato esiste già';
            }

            this.loadingResponse = false;
            return throwError(() => err)
          })
        ).subscribe((res) => {
          this.loadTags()
          this.messageService.add({
            severity: 'success',
            summary: 'Tag creato con successo'
          })
          this.isDialogVisible = false
          this.loadingResponse = false;
        })
    }
  }

  deleteTag(): void {
    this.#resetResponseStatusFields();
    this.loadingResponse = true;

    this.tagsService.deleteTag(this.targetTag!.id!)
      .pipe(
        takeUntilDestroyed(this.#destroyRef),
        catchError(err => {
          this.error = true
          this.errorMessage = 'È avvenuto un errore, contattare l\'amministratore'
          this.loadingResponse = false;
          return throwError(() => err)
        })
     ).subscribe(() => {
        this.loadTags()
        this.messageService.add({
          severity: 'success',
          summary: 'Tag cancellato con successo'
        })
        this.loadingResponse = false;
        this.closeConfirmationService()
     })
  }

  closeConfirmationService(): void {
    this.confirmationService.close()
  }
}
