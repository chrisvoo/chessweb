import { Component } from '@angular/core';
import {PageComponent} from '../components/page/page.component';

@Component({
  selector: 'admin',
  imports: [PageComponent],
  templateUrl: './admin.component.html',
  standalone: true,
  styleUrl: './admin.component.css'
})
export class AdminComponent {

}
