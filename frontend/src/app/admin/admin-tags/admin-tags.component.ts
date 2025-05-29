import { Component, DestroyRef, inject, OnInit } from '@angular/core';
import { TableModule } from 'primeng/table';
import { Button } from 'primeng/button';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { InputText } from 'primeng/inputtext';
import { Paginator } from 'primeng/paginator';
import { TooltipModule } from 'primeng/tooltip';
import { TagsService } from '../../services/tags/tags.service';
import { Tag } from '../../../types/models';
import { catchError, throwError } from 'rxjs';
import { Dialog } from 'primeng/dialog';
import {AbstractControl, FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Message } from 'primeng/message';
import { noWhiteSpaceOnly } from '../../validators/no-whitespace-only';

@Component({
  selector: 'app-admin-tags',
  imports: [TableModule, Button, IconField, InputIcon, InputText, Paginator, TooltipModule, Dialog, ReactiveFormsModule, Message],
  templateUrl: './admin-tags.component.html',
  standalone: true,
  styleUrl: './admin-tags.component.css'
})
export class AdminTagsComponent implements OnInit {
  page = 1
  pageSize = 10
  sortOrder = 1
  tags: Tag[] = []
  errorMessage: string = ''
  error: boolean = false;
  editDialogVisible = false
  tagToBeEdited?: Tag
  tagForm: FormGroup

  #destroyRef = inject(DestroyRef);

  constructor(
    private tagsService: TagsService,
    private formBuilder: FormBuilder
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

  loadTags(): void {
    this.tagsService.listTags({
      page: this.page,
      page_size: this.pageSize,
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
      const { total_items, total_pages, has_more_items, data } = res
      this.tags = res.data.items
    })
  }

  shouldDisplayErrorMessage(): boolean | undefined {
    return this.name?.invalid && (this.name?.dirty || this.name?.touched);
  }

  get name(): AbstractControl<any, any> | null {
    return this.tagForm.get('name')
  }

  onModifyItem(item: Tag): void {
    this.tagToBeEdited = item
    this.tagForm.patchValue({ name: item.name })
    this.editDialogVisible = true;
  }

  onDeleteItem(item: Tag): void {
    console.log(item);
  }

  updateTag(): void {
    if (this.tagForm.valid) {
      console.log(this.name?.value)
      this.editDialogVisible = false
    }
  }
}
