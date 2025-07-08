import {Component, DestroyRef, inject, OnInit} from '@angular/core';
import {PageComponent} from '../../../page/page.component';
import {Article} from '../../../../../types/models';
import {ArticlesService} from '../../../../services/articles/articles.service';
import {ListPaginatedArticles, ListPaginatedItemsResponse} from '../../../../../types/requests';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {catchError, throwError} from 'rxjs';
import {Paginator, PaginatorState} from 'primeng/paginator';
import {ActivatedRoute} from '@angular/router';
import {ArticleViewerComponent} from '../../../article-viewer/article-viewer.component';

@Component({
  selector: 'app-news',
  imports: [PageComponent, Paginator, ArticleViewerComponent],
  templateUrl: './news.component.html',
  standalone: true,
  styleUrl: './news.component.css'
})
export class NewsComponent implements OnInit {
  articles: Article[] = []
  offset: number = 0
  pageSize: number = 5
  totalCount: number = 0

  categoryId?: number
  tagId?: number
  searchText?: string
  #destroyRef = inject(DestroyRef)

  constructor(
    private articlesService: ArticlesService,
    private activatedRoute: ActivatedRoute
  ) {
  }

  ngOnInit(): void {
    this.activatedRoute.queryParamMap.subscribe(params => {
      const catId = params.get('category_id')
      if (catId !== null && catId.trim() !== '' && !isNaN(parseInt(catId))) {
        this.categoryId = parseInt(catId)
      }

      const tagId = params.get('tag_id')
      if (tagId !== null && tagId.trim() !== '' && !isNaN(parseInt(tagId))) {
        this.tagId = parseInt(tagId)
      }

      const searchText = params.get('search_text')
      if (searchText !== null && searchText.trim() !== '') {
        this.searchText = searchText
      }

      const endpointParams: ListPaginatedArticles = this.#buildListArticlesParams()
      this.loadArticles(endpointParams)
    })
  }

  #buildListArticlesParams(): ListPaginatedArticles {
    const endpointParams: ListPaginatedArticles = {
      page: 1,
      page_size: this.pageSize,
      sort_by: 'created_at',
      sort_order: 'desc',
      skip_content: false
    }

    if (this.categoryId) {
      endpointParams.category_id = this.categoryId
    }

    if (this.tagId) {
      endpointParams.tag_id = this.tagId
    }

    if (this.searchText) {
      endpointParams.search_text = this.searchText
    }

    return endpointParams
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

  noNewsFound() {
    let message = "Nessuna notizia trovata per "
    if (this.tagId !== undefined) {
      message += 'il tag richiesto'
    }

    if (this.categoryId !== undefined) {
      message += (this.tagId !== null ? ', ' : '') + 'la categoria richiesta'
    }

    if (this.searchText !== '') {
      message += ((this.tagId !== undefined || this.categoryId !== undefined) ? ' e ' : '') + 'la parola chiave richiesta'
    }

    return message
  }

  onPageChange(event: PaginatorState): void {
    const { first } = event;

    const endpointParams: ListPaginatedArticles = {
      ...this.#buildListArticlesParams(),
      page: Math.floor((first ?? 0) / this.pageSize) + 1
    }

    this.loadArticles(endpointParams)
  }
}
