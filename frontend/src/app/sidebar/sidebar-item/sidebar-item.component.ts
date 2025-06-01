import { Component, input } from '@angular/core';

@Component({
  selector: 'sidebar-item',
  imports: [],
  template: `
    <section class="sidebar-section">
      <header class="major">
        <h2>{{ title() }}</h2>
      </header>
      <ng-content></ng-content>
    </section>
  `,
  standalone: true,
  styleUrl: './sidebar-item.component.css'
})
export class SidebarItemComponent {
  title = input.required<string>();
}
