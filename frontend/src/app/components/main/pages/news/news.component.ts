import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import {PageComponent} from '../../../page/page.component';
import {Article} from '../../../../../types/models';
import {ArticlesService} from '../../../../services/articles/articles.service';
import {ListPaginatedArticles, ListPaginatedItemsResponse} from '../../../../../types/requests';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {Paginator, PaginatorState} from 'primeng/paginator';

@Component({
  selector: 'app-news',
  imports: [PageComponent, Paginator],
  templateUrl: './news.component.html',
  standalone: true,
  styleUrl: './news.component.css'
})
export class NewsComponent implements OnInit {
  articles: Article[] = []
  offset: number = 0
  pageSize: number = 5
  totalCount: number = 0

  #destroyRef = inject(DestroyRef)

  constructor(private articlesService: ArticlesService) {
  }

  ngOnInit(): void {
    const params: ListPaginatedArticles = {
      page: 1,
      page_size: this.pageSize,
      sort_by: 'created_at',
      sort_order: 'desc',
      skip_content: false
    }
    this.loadArticles(params)
  }

  loadArticles(params: ListPaginatedArticles): void {
    this.articlesService.listArticles(params).pipe(
      takeUntilDestroyed(this.#destroyRef),
      catchError(err => {
        console.error('È avvenuto un errore, contattare l\'amministratore')
        return throwError(() => err)
      })
    ).subscribe((res: ListPaginatedItemsResponse<Article>) => {
      this.articles = res.data.items
      this.totalCount = res.data.total_items
    })
  }

  onPageChange(event: PaginatorState): void {
    const { first } = event;

    const params: ListPaginatedArticles = {
      page: Math.floor((first ?? 0) / this.pageSize) + 1,
      page_size: this.pageSize,
      sort_by: 'created_at',
      sort_order: 'desc',
      skip_content: false
    }
    this.loadArticles(params)
  }
}
