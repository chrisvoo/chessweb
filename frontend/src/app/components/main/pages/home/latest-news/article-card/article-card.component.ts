import {Component, input} from '@angular/core';

@Component({
  selector: 'chess-article-card',
  imports: [],
  templateUrl: './article-card.component.html',
  styleUrl: './article-card.component.css',
  standalone: true,
})
export class ArticleCardComponent {
  isContentTruncated = false;

  content = input.required<string, string>({
    transform: (value: string) => {
      const parser = new DOMParser()
      const doc = parser.parseFromString(value, 'text/html');
      const textContent = doc.body.textContent || '';

      if (textContent.length > 200) {
        this.isContentTruncated = true;
        return textContent.substring(0, 200) + '...';
      }
      this.isContentTruncated = false;
      return textContent;
    }
  })
  title = input.required<string>()
  id = input.required<number>()
  createdAt = input('')
}
